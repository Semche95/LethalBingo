import { Controller } from '@hotwired/stimulus'
import {
    calculateRemainingSeconds,
    formatGameDuration,
    GAME_DURATION_SECONDS,
} from './game_timer_math'
import {
    estimateServerTimeFromResponses,
    estimateServerTimeFromRoundTrip,
    hasClientClockJumped,
    parseServerTimeHeader,
} from './game_timer_anchor'
import { computeAlertColor, createThresholdState, evaluateThresholds } from './game_timer_thresholds'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override values = {
        gameStartedAt: Number,
        gameFinishedAt: Number,
        gameTotalPausedSeconds: Number,
        gameManualAdjustmentSeconds: Number,
        gameLastPausedAt: Number,
        gameIsPaused: Boolean,
        // Server timestamp (fractional seconds) shared by all clients through the
        // Mercure broadcast, or taken at mount() on first render.
        serverTime: Number,
        // Stamped empty response fetched to resync after a sleep or a clock jump.
        serverTimeUrl: String,
    }

    static override targets = ['timerDisplay', 'pauseBadge', 'pauseButton']

    declare readonly gameStartedAtValue: number | null
    declare readonly gameFinishedAtValue: number | null
    declare readonly gameTotalPausedSecondsValue: number
    declare readonly gameManualAdjustmentSecondsValue: number
    declare readonly gameLastPausedAtValue: number | null
    declare readonly gameIsPausedValue: boolean
    declare readonly serverTimeValue: number
    declare readonly serverTimeUrlValue: string
    declare readonly hasServerTimeUrlValue: boolean

    // Server time anchor (seconds), see getCurrentServerTime().
    private syncedServerTime: number = 0

    declare readonly timerDisplayTarget: HTMLElement
    declare readonly pauseBadgeTarget: HTMLElement
    declare readonly pauseButtonTarget: HTMLElement
    declare readonly hasPauseButtonTarget: boolean

    private notificationAudio: HTMLAudioElement | null = null
    private endAudio: HTMLAudioElement | null = null
    private animationFrameId: number | null = null
    private lastRenderedText: string | null = null
    // performance.now() taken when syncedServerTime was anchored.
    private clientTimeAtRender: number = 0
    private thresholdState = createThresholdState()
    // Clocks read on the previous frame, to detect a sleep between two frames.
    private previousFrameWallTime: number = 0
    private previousFrameClientTime: number = 0
    private resyncAbortController: AbortController | null = null

    private readonly onVisibilityChange = (): void => {
        if (document.visibilityState === 'visible') {
            this.updateTimerDisplay()
            this.resyncWithServer()
        }
    }

    private readonly onOnline = (): void => {
        this.resyncWithServer()
    }

    override connect() {
        const notificationSoundURL = new URL('/assets/sounds/interval.mp3', import.meta.url)
        const endSoundURL = new URL('/assets/sounds/end.mp3', import.meta.url)
        this.notificationAudio = new Audio(notificationSoundURL.href)
        this.endAudio = new Audio(endSoundURL.href)

        this.reanchor()

        // rAF rather than setInterval: no phase that could drift between clients.
        this.startTimerLoop()

        document.addEventListener('visibilitychange', this.onVisibilityChange)
        window.addEventListener('online', this.onOnline)

        this.updateTimerDisplay()
    }

    override disconnect() {
        this.resetAudio(true)
        this.notificationAudio = null
        this.endAudio = null

        this.stopTimerLoop()

        document.removeEventListener('visibilitychange', this.onVisibilityChange)
        window.removeEventListener('online', this.onOnline)

        this.resyncAbortController?.abort()
        this.resyncAbortController = null
    }

    gameIsPausedValueChanged() {
        this.updatePauseBadge()
        this.updateTimerDisplay(false)
    }

    gameStartedAtValueChanged() {
        this.updateTimerDisplay(false)
    }

    gameFinishedAtValueChanged() {
        this.updateTimerDisplay(false)
    }

    gameTotalPausedSecondsValueChanged() {
        this.updateTimerDisplay(false)
    }

    gameManualAdjustmentSecondsValueChanged() {
        this.updateTimerDisplay(false)
    }

    gameLastPausedAtValueChanged() {
        this.updateTimerDisplay(false)
    }

    serverTimeValueChanged() {
        this.reanchor()
    }

    // Every candidate can only lag behind the real server time: the current
    // estimate, the serverTime value read now (it predates its delivery) and the
    // stamped responses (docs/TIMER.md section 1). Keeping the highest one never
    // moves the anchor backward, so the display cannot flicker between seconds.
    private reanchor() {
        const now = performance.now()
        const fromResponses = estimateServerTimeFromResponses(
            [...performance.getEntriesByType('navigation'), ...performance.getEntriesByType('resource')] as PerformanceResourceTiming[],
            now,
        )

        const bestMs = Math.max(this.getCurrentServerTime(), this.serverTimeValue * 1000, fromResponses ?? 0)

        this.syncedServerTime = bestMs / 1000
        this.clientTimeAtRender = now
    }

    // One-off request, never on an interval (docs/TIMER.md section 1): only after
    // a sleep, a clock jump, a return to the tab or a network recovery, where the
    // client clock may have stalled or drifted with no broadcast to catch up.
    private async resyncWithServer() {
        if (!this.hasServerTimeUrlValue || this.resyncAbortController) {
            return
        }

        const abortController = new AbortController()
        this.resyncAbortController = abortController

        try {
            const requestStart = performance.now()
            const response = await fetch(this.serverTimeUrlValue, { cache: 'no-store', signal: abortController.signal })
            const responseArrival = performance.now()

            const sentAtSeconds = parseServerTimeHeader(response.headers.get('Server-Timing'))
            if (sentAtSeconds === null) {
                return
            }

            this.syncedServerTime = estimateServerTimeFromRoundTrip(
                this.getCurrentServerTime(),
                sentAtSeconds,
                requestStart,
                responseArrival,
                responseArrival,
            ) / 1000
            this.clientTimeAtRender = responseArrival
            // A jump after a sleep is not a natural countdown: no threshold sound.
            this.updateTimerDisplay(false)
        } catch {
            // Offline or aborted: the next wake-up, tab return or reconnection retries.
        } finally {
            if (this.resyncAbortController === abortController) {
                this.resyncAbortController = null
            }
        }
    }

    private startTimerLoop() {
        this.previousFrameWallTime = Date.now()
        this.previousFrameClientTime = performance.now()

        const tick = () => {
            this.detectClientClockJump()
            this.updateTimerDisplay()
            this.animationFrameId = window.requestAnimationFrame(tick)
        }
        this.animationFrameId = window.requestAnimationFrame(tick)
    }

    // Date.now() only serves to detect the jump, never to compute the time.
    private detectClientClockJump() {
        const wallTime = Date.now()
        const clientTime = performance.now()

        if (hasClientClockJumped(this.previousFrameWallTime, this.previousFrameClientTime, wallTime, clientTime)) {
            this.resyncWithServer()
        }

        this.previousFrameWallTime = wallTime
        this.previousFrameClientTime = clientTime
    }

    private stopTimerLoop() {
        if (this.animationFrameId !== null) {
            window.cancelAnimationFrame(this.animationFrameId)
            this.animationFrameId = null
        }
    }

    private updateTimerDisplay(canTriggerSound: boolean = true) {
        const formattedTime = this.formatGameDisplay()

        if (formattedTime !== this.lastRenderedText) {
            this.lastRenderedText = formattedTime
            this.timerDisplayTarget.textContent = formattedTime
        }

        if (this.hasPauseButtonTarget) {
            this.pauseButtonTarget.classList.toggle('d-none', formattedTime === '00:00:00')
        }

        this.updatePauseBadge()
        this.updateHighlighting(canTriggerSound)
    }

    private updatePauseBadge() {
        if (this.gameIsPausedValue) {
            this.pauseBadgeTarget.classList.remove('d-none')
        } else {
            this.pauseBadgeTarget.classList.add('d-none')
        }
    }

    private updateHighlighting(canTriggerSound: boolean = true) {
        const remainingSeconds = this.computeRemainingSeconds()

        // Evaluated whatever the game state so the sound reference never goes stale
        // (docs/TIMER.md section 5).
        const alert = evaluateThresholds(remainingSeconds, GAME_DURATION_SECONDS, this.thresholdState, canTriggerSound)

        if (!this.gameStartedAtValue || this.gameFinishedAtValue || this.gameIsPausedValue) {
            this.timerDisplayTarget.className = ''
            return
        }

        if (alert === 'end') {
            this.playEndOfTimerSound()
        } else if (alert) {
            this.playNotificationSound()
        }

        const color = computeAlertColor(remainingSeconds, GAME_DURATION_SECONDS)
        this.timerDisplayTarget.className = color ? `text-${color} animate-pulse` : ''
    }

    /**
     * Estimates the current server time in milliseconds from the last anchor
     * plus the monotonic client time elapsed since.
     */
    private getCurrentServerTime(): number {
        const clientElapsedTime = performance.now() - this.clientTimeAtRender
        return (this.syncedServerTime * 1000) + clientElapsedTime
    }

    private computeRemainingSeconds(): number {
        return calculateRemainingSeconds({
            gameStartedAt: this.gameStartedAtValue,
            gameFinishedAt: this.gameFinishedAtValue,
            gameTotalPausedSeconds: this.gameTotalPausedSecondsValue,
            gameManualAdjustmentSeconds: this.gameManualAdjustmentSecondsValue,
            gameLastPausedAt: this.gameLastPausedAtValue,
            gameIsPaused: this.gameIsPausedValue,
            currentServerTime: this.getCurrentServerTime(),
        })
    }

    private formatGameDisplay(): string {
        return formatGameDuration(
            this.gameStartedAtValue,
            this.gameFinishedAtValue,
            this.computeRemainingSeconds(),
        )
    }

    private resetAudio(cleanup = false) {
        if (this.notificationAudio) {
            this.notificationAudio.pause()
            this.notificationAudio.currentTime = 0
            if (cleanup) this.notificationAudio.src = ''
        }

        if (this.endAudio) {
            this.endAudio.pause()
            this.endAudio.currentTime = 0
            if (cleanup) this.endAudio.src = ''
        }
    }

    private playAudio(audio: HTMLAudioElement | null, errorType: string) {
        // Don't play sounds if the game is finished
        if (this.gameFinishedAtValue) {
            return
        }

        try {
            if (audio) {
                audio.pause()
                audio.currentTime = 0
                audio.play().catch(e => console.error(`Error playing ${errorType}: ${e}`))
            }
        } catch (e) {
            console.error(`${errorType} sound failed: ${e}`)
        }
    }

    private playNotificationSound() {
        this.playAudio(this.notificationAudio, 'notification')
    }

    private playEndOfTimerSound() {
        this.playAudio(this.endAudio, 'end')
    }
}
