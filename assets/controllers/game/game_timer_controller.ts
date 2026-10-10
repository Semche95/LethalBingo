import { Controller } from '@hotwired/stimulus'
import {
    calculateExactRemainingSeconds,
    calculateRemainingSeconds,
    formatGameDuration,
    GAME_DURATION_SECONDS,
    type RemainingSecondsParams,
} from './game_timer_math'
import {
    estimateServerTimeFromResponses,
    estimateServerTimeFromRoundTrip,
    parseServerTimeHeader,
} from './game_timer_anchor'
import { watchWakeUps } from '../../wake_up'
import { TimerSound, type TimerSoundName } from './game_timer_sound'
import { computeAlertColor, createThresholdState, evaluateThresholds, listUpcomingAlerts } from './game_timer_thresholds'

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

    static override targets = ['timerDisplay', 'pauseBadge', 'pauseButton', 'soundButton']

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
    declare readonly soundButtonTarget: HTMLElement
    declare readonly hasSoundButtonTarget: boolean

    private sound: TimerSound | null = null
    private stopSoundLockWatch: (() => void) | null = null
    private animationFrameId: number | null = null
    private lastRenderedText: string | null = null
    // performance.now() taken when syncedServerTime was anchored.
    private clientTimeAtRender: number = 0
    private thresholdState = createThresholdState()
    private stopWakeUpWatch: (() => void) | null = null
    private resyncAbortController: AbortController | null = null

    private readonly onVisibilityChange = (): void => {
        this.updateScheduledSounds()

        if (document.visibilityState === 'visible') {
            // The thresholds crossed while hidden already sounded on time.
            this.updateTimerDisplay(false)
            this.resyncWithServer()
        }
    }

    private readonly onUserGesture = (): void => {
        this.sound?.unlock()
    }

    private readonly updateSoundButton = (): void => {
        if (this.hasSoundButtonTarget) {
            this.soundButtonTarget.classList.toggle('d-none', !this.sound?.isLocked)
        }
    }

    enableSound() {
        this.sound?.unlock()
    }

    private readonly onWakeUp = (): void => {
        this.resyncWithServer()
    }

    override connect() {
        this.sound = new TimerSound({
            notification: new URL('/assets/sounds/interval.mp3', import.meta.url).href,
            end: new URL('/assets/sounds/end.mp3', import.meta.url).href,
        })
        this.stopSoundLockWatch = this.sound.onLockChange(this.updateSoundButton)
        this.updateSoundButton()
        // Any interaction with the page unlocks the sound, not only the button.
        document.addEventListener('click', this.onUserGesture)
        document.addEventListener('keydown', this.onUserGesture)

        this.reanchor()

        // rAF rather than setInterval: no phase that could drift between clients.
        this.startTimerLoop()
        this.stopWakeUpWatch = watchWakeUps(this.onWakeUp)
        document.addEventListener('visibilitychange', this.onVisibilityChange)

        this.updateTimerDisplay()
        this.updateScheduledSounds()
    }

    override disconnect() {
        document.removeEventListener('click', this.onUserGesture)
        document.removeEventListener('keydown', this.onUserGesture)
        this.stopSoundLockWatch?.()
        this.stopSoundLockWatch = null
        this.sound?.close()
        this.sound = null

        this.stopTimerLoop()
        this.stopWakeUpWatch?.()
        this.stopWakeUpWatch = null
        document.removeEventListener('visibilitychange', this.onVisibilityChange)

        this.resyncAbortController?.abort()
        this.resyncAbortController = null
    }

    gameIsPausedValueChanged() {
        this.updatePauseBadge()
        this.onGameStateChanged()
    }

    gameStartedAtValueChanged() {
        this.onGameStateChanged()
    }

    gameFinishedAtValueChanged() {
        this.onGameStateChanged()
    }

    gameTotalPausedSecondsValueChanged() {
        this.onGameStateChanged()
    }

    gameManualAdjustmentSecondsValueChanged() {
        this.onGameStateChanged()
    }

    gameLastPausedAtValueChanged() {
        this.onGameStateChanged()
    }

    serverTimeValueChanged() {
        this.reanchor()
        this.updateScheduledSounds()
    }

    // Referee actions are not a natural countdown: no threshold sound.
    private onGameStateChanged() {
        this.updateTimerDisplay(false)
        this.updateScheduledSounds()
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
            this.updateScheduledSounds()
        } catch {
            // Offline or aborted: the next wake-up, tab return or reconnection retries.
        } finally {
            if (this.resyncAbortController === abortController) {
                this.resyncAbortController = null
            }
        }
    }

    private startTimerLoop() {
        const tick = () => {
            this.updateTimerDisplay()
            this.animationFrameId = window.requestAnimationFrame(tick)
        }
        this.animationFrameId = window.requestAnimationFrame(tick)
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
        this.pauseBadgeTarget.classList.toggle('d-none', !this.gameIsPausedValue)
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
            this.playSound('end')
        } else if (alert) {
            this.playSound('notification')
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
        return calculateRemainingSeconds(this.remainingSecondsParams())
    }

    private remainingSecondsParams(): RemainingSecondsParams {
        return {
            gameStartedAt: this.gameStartedAtValue,
            gameFinishedAt: this.gameFinishedAtValue,
            gameTotalPausedSeconds: this.gameTotalPausedSecondsValue,
            gameManualAdjustmentSeconds: this.gameManualAdjustmentSecondsValue,
            gameLastPausedAt: this.gameLastPausedAtValue,
            gameIsPaused: this.gameIsPausedValue,
            currentServerTime: this.getCurrentServerTime(),
        }
    }

    private formatGameDisplay(): string {
        return formatGameDuration(
            this.gameStartedAtValue,
            this.gameFinishedAtValue,
            this.computeRemainingSeconds(),
        )
    }

    private playSound(name: TimerSoundName) {
        // Don't play sounds if the game is finished. While the page is hidden,
        // the scheduled sounds play instead (updateScheduledSounds()).
        if (this.gameFinishedAtValue || document.visibilityState === 'hidden') {
            return
        }

        this.sound?.play(name)
    }

    // The browser stops the display loop while the page is hidden (other tab,
    // minimized window), so the threshold sounds are scheduled ahead on the
    // audio clock instead, and rescheduled on every change of the countdown
    // (docs/TIMER.md section 5).
    private updateScheduledSounds() {
        if (!this.sound) {
            return
        }

        const isCountingDown = this.gameStartedAtValue && !this.gameFinishedAtValue && !this.gameIsPausedValue
        if (document.visibilityState !== 'hidden' || !isCountingDown) {
            this.sound.cancelScheduled()
            return
        }

        const exactRemainingSeconds = calculateExactRemainingSeconds(this.remainingSecondsParams())
        this.sound.schedule(listUpcomingAlerts(exactRemainingSeconds, GAME_DURATION_SECONDS).map(({ alert, inSeconds }) => ({
            name: alert === 'end' ? 'end' : 'notification',
            inSeconds,
        })))
    }
}
