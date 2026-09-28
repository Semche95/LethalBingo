import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { Application } from '@hotwired/stimulus'
import GameTimerController from '../controllers/game/game_timer_controller'
import { createThresholdState } from '../controllers/game/game_timer_thresholds'
import { FakeAudioContext, installFakeAudioContext } from './fake_audio_context'

// ---------------------------------------------------------------------------
// Test environment setup
// ---------------------------------------------------------------------------


let app: Application

function kebab(name: string): string {
    return name.replace(/[A-Z]/g, (m) => '-' + m.toLowerCase())
}

interface TimerValues {
    gameStartedAt?: number | null
    gameFinishedAt?: number | null
    gameTotalPausedSeconds?: number
    gameManualAdjustmentSeconds?: number
    gameLastPausedAt?: number | null
    gameIsPaused?: boolean
    serverTime?: number
    serverTimeUrl?: string
}

const SERVER_TIME = 1_700_000_000
const START_SEC = SERVER_TIME - 1800 // 30 minutes elapsed -> 00:30:00 remaining

function buildElement(values: TimerValues = {}) {
    const el = document.createElement('div')
    el.setAttribute('data-controller', 'game--game-timer')

    for (const [key, val] of Object.entries(values)) {
        if (val === null || val === undefined) continue
        el.setAttribute(`data-game--game-timer-${kebab(key)}-value`, String(val))
    }

    el.innerHTML = `
        <span data-game--game-timer-target="timerDisplay"></span>
        <span data-game--game-timer-target="pauseBadge" class="d-none"></span>
        <button data-game--game-timer-target="pauseButton"></button>
        <button data-game--game-timer-target="soundButton" class="d-none"></button>
    `
    document.body.appendChild(el)
    return el
}

// Records the sounds requested by the controller ('notification' or 'end').
function spyOnSounds(controller: any) {
    const play = vi.spyOn(controller.sound, 'play')
    return (name: string) => play.mock.calls.filter(([played]) => played === name).length
}

async function connectController(el: HTMLElement): Promise<any> {
    for (let i = 0; i < 50; i++) {
        const controller = app.getControllerForElementAndIdentifier(el, 'game--game-timer')
        if (controller) return controller
        await Promise.resolve()
    }
    throw new Error('Controller never connected')
}

// The fake performance.now() may return the real epoch on its first read, which
// would skew the client-time estimate. Normalizing the anchor right after connect
// makes the delta start at 0 and keeps the estimate fully deterministic.
//
// connect()'s own initial updateTimerDisplay() runs BEFORE this normalization, so
// with the skewed first read it may spuriously seed the SOUND detection reference
// with a wrong remaining value. That never happens on a real page load (correct
// serverTime + real clock), so we reset the sound threshold state here to the
// clean post-connect slate a real controller would have. Colour needs no reset:
// it is a pure function of the current remaining time, recomputed every frame.
async function connectWithAnchor(values: TimerValues = {}): Promise<any> {
    const el = buildElement(values)
    const controller = await connectController(el)
    controller.syncedServerTime = values.serverTime ?? SERVER_TIME
    controller.clientTimeAtRender = performance.now()
    controller.thresholdState = createThresholdState()
    controller.timerDisplayTarget.className = ''
    controller.updateTimerDisplay()
    return controller
}

beforeEach(async () => {
    vi.useFakeTimers({
        toFake: [
            'setTimeout',
            'setInterval',
            'clearTimeout',
            'clearInterval',
            'Date',
            'performance',
            'requestAnimationFrame',
            'cancelAnimationFrame',
        ],
    })

    installFakeAudioContext()

    app = new Application(document.documentElement)
    app.register('game--game-timer', GameTimerController)
    await app.start()
})

afterEach(() => {
    delete (document as any).visibilityState
    app.stop()
    document.body.innerHTML = ''
    vi.runOnlyPendingTimers()
    vi.useRealTimers()
    vi.clearAllMocks()
})

describe('game_timer_controller', () => {
    it('renders the initial remaining time on connect', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: START_SEC,
            gameIsPaused: false,
        })

        expect(controller.timerDisplayTarget.textContent).toBe('00:30:00')
    })

    it('drives the display with requestAnimationFrame and updates as time passes', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: START_SEC,
            gameIsPaused: false,
        })
        expect(controller.timerDisplayTarget.textContent).toBe('00:30:00')

        // Advance fake rAF frames so the countdown ticks down.
        await vi.advanceTimersByTimeAsync(2000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:29:58')
    })

    it('accounts for a positive manual adjustment in the display', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: START_SEC,
            gameIsPaused: false,
            gameManualAdjustmentSeconds: 60,
        })

        expect(controller.timerDisplayTarget.textContent).toBe('00:31:00')
    })

    it('re-anchors the client clock on a Live re-render (serverTimeValueChanged)', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: START_SEC,
            gameIsPaused: false,
        })

        // Let the client clock tick forward a little, then simulate the Live
        // re-render writing a fresh server timestamp.
        await vi.advanceTimersByTimeAsync(100)
        const anchorBefore = controller.clientTimeAtRender
        controller.serverTimeValue = SERVER_TIME + 10
        controller.serverTimeValueChanged()

        expect(controller.syncedServerTime).toBe(SERVER_TIME + 10)
        expect(controller.clientTimeAtRender).not.toBe(anchorBefore)
        // With the new anchor the game has 1810s elapsed -> 00:29:50 remaining.
        controller.updateTimerDisplay()
        expect(controller.timerDisplayTarget.textContent).toBe('00:29:50')
    })

    it('rejects a re-anchor captured before transmission (never rewinds the estimate)', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: START_SEC,
            gameIsPaused: false,
        })

        // Let the client clock tick forward: the extrapolated estimate is now
        // ahead of the anchor by the elapsed time.
        await vi.advanceTimersByTimeAsync(2000)
        const estimateBefore = controller.getCurrentServerTime()

        // Simulate a Live re-render delivering a broadcast timestamp captured
        // before transmission: newer than the current anchor but still behind the
        // extrapolated estimate. Re-applying it would rewind the display.
        controller.serverTimeValue = (estimateBefore - 500) / 1000
        controller.serverTimeValueChanged()
        expect(controller.getCurrentServerTime()).toBe(estimateBefore)
    })

    it('accepts a genuinely newer re-anchor (e.g. after the machine slept)', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: START_SEC,
            gameIsPaused: false,
        })

        // performance.now() froze while the machine slept: the estimate is stale
        // and the first fresh broadcast is ahead of it, so it must be accepted.
        const freshServerTime = controller.getCurrentServerTime() / 1000 + 60
        controller.serverTimeValue = freshServerTime
        controller.serverTimeValueChanged()

        expect(controller.syncedServerTime).toBe(freshServerTime)
        expect(controller.getCurrentServerTime()).toBeGreaterThanOrEqual(freshServerTime * 1000)
    })

    it('shows warning colour only while remaining is within 5s below a 10-minute mark', async () => {
        // startedAt so remaining starts at exactly 00:40:00 (2400s).
        const controller = await connectWithAnchor({
            gameStartedAt: SERVER_TIME - 1200,
            gameIsPaused: false,
        })
        expect(controller.timerDisplayTarget.className).toBe('')

        // Just below 00:40:00 (within 5s) -> warning colour.
        await vi.advanceTimersByTimeAsync(2000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:39:58')
        expect(controller.timerDisplayTarget.className).toContain('text-warning')

        // More than 5s below the mark -> the colour disappears by itself (pure
        // function, no timer/expiry state).
        await vi.advanceTimersByTimeAsync(5000)
        expect(controller.timerDisplayTarget.className).toBe('')
    })

    it('keeps the last minute in continuous danger, including past 5s', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: SERVER_TIME - 3541,
            gameIsPaused: false,
        })
        expect(controller.timerDisplayTarget.textContent).toBe('00:00:59')
        expect(controller.timerDisplayTarget.className).toContain('text-danger')

        await vi.advanceTimersByTimeAsync(10_000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:00:49')
        expect(controller.timerDisplayTarget.className).toContain('text-danger')
    })

    it('removes the colour immediately on set-to above the threshold and reapplies it on the natural re-cross (spec section 5/6)', async () => {
        // startedAt so remaining starts at 00:30:41 (1841s), just above 00:30:00.
        const controller = await connectWithAnchor({
            gameStartedAt: SERVER_TIME - 1759,
            gameIsPaused: false,
        })

        // Natural countdown crosses 00:30:00 -> warning colour.
        await vi.advanceTimersByTimeAsync(42_000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:29:59')
        expect(controller.timerDisplayTarget.className).toContain('text-warning')

        // Direct set-to 00:30:03 (above the threshold): the colour must vanish
        // immediately — 00:30:03 is not within 5s below a 10-minute multiple.
        controller.gameManualAdjustmentSecondsValue = 4
        controller.updateTimerDisplay()
        expect(controller.timerDisplayTarget.textContent).toBe('00:30:03')
        expect(controller.timerDisplayTarget.className).toBe('')

        // Natural countdown re-passes under 00:30:00 -> the colour reappears
        // automatically (pure function of the current remaining time).
        await vi.advanceTimersByTimeAsync(4000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:29:59')
        expect(controller.timerDisplayTarget.className).toContain('text-warning')
    })

    it('never oscillates between two adjacent displayed values under Live re-anchors (single display source)', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: START_SEC, // 00:30:00 remaining
            gameIsPaused: false,
        })

        // Walk the countdown frame by frame through the 00:30:00 boundary while
        // periodically simulating a Live re-render (the serverTime anchor is
        // re-written, re-anchoring the client clock exactly as serverTimeValueChanged
        // does). The displayed value must stay monotonic: the client is the single
        // writer of the display, so it can never alternate A, B, A with B != A.
        const samples: string[] = []
        for (let t = 0; t < 5000; t += 16) {
            if (t > 0 && t % 700 === 0) {
                controller.syncedServerTime = SERVER_TIME + t / 1000
                controller.clientTimeAtRender = performance.now()
            }
            await vi.advanceTimersByTimeAsync(16)
            samples.push(controller.timerDisplayTarget.textContent)
        }

        for (let i = 2; i < samples.length; i++) {
            if (samples[i] === samples[i - 2]) {
                expect(samples[i]).toBe(samples[i - 1])
            }
        }
    })

    // -----------------------------------------------------------------------
    // Sound gating: only natural countdown may trigger sound (spec section 5).
    // -----------------------------------------------------------------------

    it('triggers sound on natural countdown crossing a threshold', async () => {
        // Start at 00:05:01 remaining so natural countdown crosses 00:05:00.
        // remaining = 3600 - (SERVER_TIME - startedAt) = 3600 - 3299 = 301.
        const controller = await connectWithAnchor({
            gameStartedAt: SERVER_TIME - 3299,
            gameIsPaused: false,
        })
        const plays = spyOnSounds(controller)

        // Natural countdown crosses 00:05:00 → sound must fire.
        await vi.advanceTimersByTimeAsync(2000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:04:59')
        expect(plays('notification')).toBeGreaterThan(0)
    })

    it('does NOT trigger sound on manual −1 minute crossing a threshold', async () => {
        // Start at 00:06:00 remaining (360s), well above the 00:05:00 threshold.
        // remaining = 3600 - 3240 = 360.
        const controller = await connectWithAnchor({
            gameStartedAt: SERVER_TIME - 3240,
            gameIsPaused: false,
        })
        const plays = spyOnSounds(controller)

        // Let rAF seed the reference at ~360s.
        await vi.advanceTimersByTimeAsync(100)
        expect(controller.timerDisplayTarget.textContent).toBe('00:06:00')

        const playsBefore = plays('notification')

        // Simulate a −1 minute manual adjustment: the remaining time drops from
        // ~360s to ~300s, crossing the 00:05:00 threshold. The sound must NOT
        // fire because this is a manual mutation, not natural countdown.
        controller.gameManualAdjustmentSecondsValue = -60
        controller.gameManualAdjustmentSecondsValueChanged()
        expect(controller.timerDisplayTarget.textContent).toBe('00:05:00')

        expect(plays('notification')).toBe(playsBefore)
    })

    it('plays the end sound (not the notification) when the countdown reaches zero', async () => {
        // 00:00:02 remaining: remaining = 3600 - 3598 = 2.
        const controller = await connectWithAnchor({
            gameStartedAt: SERVER_TIME - 3598,
            gameIsPaused: false,
        })
        const plays = spyOnSounds(controller)
        const notificationsBefore = plays('notification')

        await vi.advanceTimersByTimeAsync(3000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:00:00')
        expect(plays('end')).toBe(1)
        expect(plays('notification')).toBe(notificationsBefore)
        expect(controller.pauseButtonTarget.classList.contains('d-none')).toBe(true)

        // Overtime: staying at zero never replays the end sound.
        await vi.advanceTimersByTimeAsync(5000)
        expect(plays('end')).toBe(1)
    })

    it('does NOT play the end sound on a manual −1 minute reaching zero, but does on the later natural zero', async () => {
        // 00:00:30 remaining: remaining = 3600 - 3570 = 30.
        const controller = await connectWithAnchor({
            gameStartedAt: SERVER_TIME - 3570,
            gameIsPaused: false,
        })
        const plays = spyOnSounds(controller)

        await vi.advanceTimersByTimeAsync(100)

        // Manual −1 minute: reaches zero silently, pause button hidden.
        controller.gameManualAdjustmentSecondsValue = -60
        controller.gameManualAdjustmentSecondsValueChanged()
        expect(controller.timerDisplayTarget.textContent).toBe('00:00:00')
        expect(controller.pauseButtonTarget.classList.contains('d-none')).toBe(true)
        expect(plays('end')).toBe(0)

        // Manual +1 minute brings the time back: pause button visible again.
        controller.gameManualAdjustmentSecondsValue = 0
        controller.gameManualAdjustmentSecondsValueChanged()
        expect(controller.timerDisplayTarget.textContent).not.toBe('00:00:00')
        expect(controller.pauseButtonTarget.classList.contains('d-none')).toBe(false)
        expect(plays('end')).toBe(0)

        // Natural countdown reaches zero → the end sound MUST fire.
        await vi.advanceTimersByTimeAsync(31_000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:00:00')
        expect(plays('end')).toBe(1)
    })

    describe('server time anchor', () => {
        // Simulates a response stamped with the server send time that arrived
        // arrivedMsAgo milliseconds before now.
        function stubStampedResponse(sentAtSeconds: number, arrivedMsAgo: number) {
            const entry = {
                responseStart: performance.now() - arrivedMsAgo,
                serverTiming: [{ name: 'app-time', description: String(sentAtSeconds) }],
            }
            Object.defineProperty(performance, 'getEntriesByType', {
                value: (type: string) => type === 'navigation' ? [entry] : [],
                configurable: true,
            })
        }

        function estimatedServerTimeMs(controller: any): number {
            return controller.getCurrentServerTime()
        }

        it('anchors on the stamped response when the rendered serverTime is older', async () => {
            await vi.advanceTimersByTimeAsync(5_000)
            // Rendered at SERVER_TIME, sent 70ms later, arrived 1.5s ago.
            stubStampedResponse(SERVER_TIME + 0.07, 1_500)

            const controller = await connectController(buildElement({ gameStartedAt: START_SEC, serverTime: SERVER_TIME }))

            expect(estimatedServerTimeMs(controller)).toBeCloseTo(SERVER_TIME * 1000 + 70 + 1_500, 3)
        })

        it('falls back to the rendered serverTime without any stamped response', async () => {
            await vi.advanceTimersByTimeAsync(5_000)
            Object.defineProperty(performance, 'getEntriesByType', { value: () => [], configurable: true })

            const controller = await connectController(buildElement({ gameStartedAt: START_SEC, serverTime: SERVER_TIME }))

            expect(estimatedServerTimeMs(controller)).toBeCloseTo(SERVER_TIME * 1000, 3)
        })
    })

    describe('server resync', () => {
        const SERVER_TIME_URL = '/server-time'

        function stubServerTimeResponse(sentAtSeconds: number | null) {
            const headers = new Headers(sentAtSeconds === null ? {} : { 'Server-Timing': `app-time;desc="${sentAtSeconds}"` })
            const fetchMock = vi.fn().mockResolvedValue({ headers })
            vi.stubGlobal('fetch', fetchMock)
            return fetchMock
        }

        async function flushPromises() {
            for (let i = 0; i < 10; i++) {
                await Promise.resolve()
            }
        }

        function showTab() {
            Object.defineProperty(document, 'visibilityState', { value: 'visible', configurable: true })
            document.dispatchEvent(new Event('visibilitychange'))
        }

        afterEach(() => {
            vi.unstubAllGlobals()
        })

        it('catches up on the server time after a sleep once the tab is visible again', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            // The client clock stalled during a 60s sleep: the server moved on.
            const fetchMock = stubServerTimeResponse(SERVER_TIME + 60)

            showTab()
            await flushPromises()

            expect(fetchMock).toHaveBeenCalledWith(SERVER_TIME_URL, expect.objectContaining({ cache: 'no-store' }))
            expect(controller.timerDisplayTarget.textContent).toBe('00:29:00')
        })

        it('resyncs when the wall clock jumps ahead of the stalled client clock', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            const fetchMock = stubServerTimeResponse(SERVER_TIME + 60)

            // Only the wall clock moves, as performance.now() does during a sleep.
            vi.setSystemTime(Date.now() + 60_000)
            await vi.advanceTimersByTimeAsync(20)
            await flushPromises()

            expect(fetchMock).toHaveBeenCalledTimes(1)
            expect(controller.timerDisplayTarget.textContent).toBe('00:29:00')
        })


        it('does not resync while both clocks run together', async () => {
            await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            const fetchMock = stubServerTimeResponse(SERVER_TIME)

            await vi.advanceTimersByTimeAsync(5_000)

            expect(fetchMock).not.toHaveBeenCalled()
        })

        it('moves the anchor backward only when the client clock is proven ahead', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            // The client clock ran 2s fast; the server replies within the round trip.
            controller.syncedServerTime = SERVER_TIME + 2
            stubServerTimeResponse(SERVER_TIME)

            showTab()
            await flushPromises()

            expect(controller.getCurrentServerTime()).toBeCloseTo(SERVER_TIME * 1000, 3)
            expect(controller.timerDisplayTarget.textContent).toBe('00:30:00')
        })

        it('keeps the anchor when the response carries no server time', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            stubServerTimeResponse(null)

            showTab()
            await flushPromises()

            expect(controller.getCurrentServerTime()).toBeCloseTo(SERVER_TIME * 1000, 3)
        })
    })

    describe('sound unlock', () => {
        async function flushPromises() {
            for (let i = 0; i < 10; i++) {
                await Promise.resolve()
            }
        }

        it('shows the enable sound button while the browser blocks sound, until the user clicks the page', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC })
            expect(controller.soundButtonTarget.classList.contains('d-none')).toBe(false)

            document.body.click()
            await flushPromises()

            expect(FakeAudioContext.instances[0].resume).toHaveBeenCalled()
            expect(controller.soundButtonTarget.classList.contains('d-none')).toBe(true)
        })

        it('hides the button when sound is already allowed', async () => {
            FakeAudioContext.initialState = 'running'

            const controller = await connectWithAnchor({ gameStartedAt: START_SEC })

            expect(controller.soundButtonTarget.classList.contains('d-none')).toBe(true)
        })

        it('releases the audio context and the page listeners on disconnect', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC })
            const context = FakeAudioContext.instances[0]

            controller.element.remove()
            await flushPromises()
            document.body.click()

            expect(context.close).toHaveBeenCalled()
            expect(context.resume).not.toHaveBeenCalled()
        })
    })

    describe('sounds while the page is hidden', () => {
        async function flushPromises() {
            for (let i = 0; i < 10; i++) {
                await Promise.resolve()
            }
        }

        function setVisibility(state: DocumentVisibilityState) {
            Object.defineProperty(document, 'visibilityState', { value: state, configurable: true })
            document.dispatchEvent(new Event('visibilitychange'))
        }

        // Pending sounds on the audio clock, as [name, start time].
        function pendingSounds(): Array<[string, number]> {
            return FakeAudioContext.instances[0].starts
                .filter(({ stopped }) => !stopped)
                .map(({ buffer, when }) => [String((buffer as any).decodedFrom).includes('end') ? 'end' : 'notification', when])
        }

        beforeEach(() => {
            FakeAudioContext.initialState = 'running'
            vi.stubGlobal('fetch', vi.fn(async (url: string) => ({ arrayBuffer: async () => url })))
        })

        afterEach(() => {
            vi.unstubAllGlobals()
        })

        it('schedules the upcoming alerts on the audio clock once the page is hidden', async () => {
            // 00:05:01 remaining: the display drops below 05:00 in 2 s.
            await connectWithAnchor({ gameStartedAt: SERVER_TIME - 3299 })
            expect(pendingSounds()).toEqual([])

            setVisibility('hidden')
            await flushPromises()

            expect(pendingSounds()).toEqual([
                ['notification', 2],
                ['notification', 122],
                ['notification', 242],
                ['end', 301],
            ])
        })

        it('does not replay on return a threshold that sounded while hidden', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: SERVER_TIME - 3299 })
            const plays = spyOnSounds(controller)
            setVisibility('hidden')
            await flushPromises()

            // The display loop is stopped while hidden: time passes without frames.
            controller.clientTimeAtRender -= 3_000
            setVisibility('visible')
            await vi.advanceTimersByTimeAsync(100)

            expect(controller.timerDisplayTarget.textContent).toBe('00:04:58')
            expect(plays('notification')).toBe(0)
            expect(pendingSounds()).toEqual([])
        })

        it('cancels the scheduled alerts when the referee pauses, and reschedules on resume', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: SERVER_TIME - 3299 })
            setVisibility('hidden')
            await flushPromises()

            controller.gameLastPausedAtValue = SERVER_TIME
            controller.gameIsPausedValue = true
            await flushPromises()
            expect(pendingSounds()).toEqual([])

            controller.gameIsPausedValue = false
            controller.gameTotalPausedSecondsValue = 10
            await flushPromises()
            expect(pendingSounds()[0]).toEqual(['notification', 12])
        })

        it('reschedules after a +1 minute from the referee', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: SERVER_TIME - 3299 })
            setVisibility('hidden')
            await flushPromises()

            controller.gameManualAdjustmentSecondsValue = 60
            await flushPromises()

            expect(pendingSounds()[0]).toEqual(['notification', 62])
        })
    })
})
