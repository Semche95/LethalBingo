import { describe, it, expect, vi } from 'vitest'
import { SERVER_TIME, START_SEC, spyOnSounds, connectWithAnchor, useTimerControllerApp } from './game_timer_controller_setup'

describe('game_timer_controller', () => {
    useTimerControllerApp()

    it('renders the initial remaining time on connect', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: START_SEC,
            gameIsPaused: false,
        })

        expect(controller.timerDisplayTarget.textContent).toBe('00:30:00')
    })

    it('shows the countdown duration of the game before it starts', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: null,
            countdownDurationSeconds: 5400,
        })

        expect(controller.timerDisplayTarget.textContent).toBe('01:30:00')
    })

    it('counts the elapsed time of a stopwatch, without alert colour nor sound', async () => {
        const controller = await connectWithAnchor({
            timerMode: 'stopwatch',
            gameStartedAt: SERVER_TIME - 599,
            gamePausedDurationSeconds: 0,
        })
        const plays = spyOnSounds(controller)
        expect(controller.timerDisplayTarget.textContent).toBe('00:09:59')

        await vi.advanceTimersByTimeAsync(2000)

        expect(controller.timerDisplayTarget.textContent).toBe('00:10:01')
        expect(controller.timerDisplayTarget.className).toBe('')
        expect(plays('notification') + plays('end')).toBe(0)
        expect(controller.pauseButtonTarget.classList.contains('d-none')).toBe(false)
    })

    it('freezes the stopwatch at the start of the pause, pauses excluded', async () => {
        const controller = await connectWithAnchor({
            timerMode: 'stopwatch',
            gameStartedAt: SERVER_TIME - 3600,
            gameIsPaused: true,
            gamePausedAt: SERVER_TIME - 600,
            gamePausedDurationSeconds: 1200,
        })
        expect(controller.timerDisplayTarget.textContent).toBe('00:30:00')

        await vi.advanceTimersByTimeAsync(5000)

        expect(controller.timerDisplayTarget.textContent).toBe('00:30:00')
        expect(controller.pauseBadgeTarget.classList.contains('d-none')).toBe(false)
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
            gameCountdownZeroAt: START_SEC + 3660,
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
        // immediately: 00:30:03 is not within 5s below a 10-minute multiple.
        controller.gameCountdownZeroAtValue += 4
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
        controller.gameCountdownZeroAtValue -= 60
        controller.gameCountdownZeroAtValueChanged()
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

        // Manual −1 minute: the server puts the zero at the current time
        // (never below zero), reached silently, pause button hidden.
        controller.gameCountdownZeroAtValue = controller.getCurrentServerTime() / 1000
        controller.gameCountdownZeroAtValueChanged()
        expect(controller.timerDisplayTarget.textContent).toBe('00:00:00')
        expect(controller.pauseButtonTarget.classList.contains('d-none')).toBe(true)
        expect(plays('end')).toBe(0)

        // Manual +1 minute brings the time back: pause button visible again.
        controller.gameCountdownZeroAtValue = controller.getCurrentServerTime() / 1000 + 60
        controller.gameCountdownZeroAtValueChanged()
        expect(controller.timerDisplayTarget.textContent).toBe('00:01:00')
        expect(controller.pauseButtonTarget.classList.contains('d-none')).toBe(false)
        expect(plays('end')).toBe(0)

        // Natural countdown reaches zero → the end sound MUST fire.
        await vi.advanceTimersByTimeAsync(61_000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:00:00')
        expect(plays('end')).toBe(1)
    })

    it('hides the pause button from the computed remaining time', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: SERVER_TIME - 3598,
            gameIsPaused: false,
        })
        expect(controller.pauseButtonTarget.classList.contains('d-none')).toBe(false)

        await vi.advanceTimersByTimeAsync(3000)
        expect(controller.pauseButtonTarget.classList.contains('d-none')).toBe(true)

        controller.gameCountdownZeroAtValue = controller.getCurrentServerTime() / 1000 + 60
        controller.gameCountdownZeroAtValueChanged()
        expect(controller.pauseButtonTarget.classList.contains('d-none')).toBe(false)
    })

    it('shows the frozen remaining time during a pause', async () => {
        const controller = await connectWithAnchor({
            gameStartedAt: START_SEC,
            gameIsPaused: true,
            gamePausedRemainingSeconds: 59.3,
        })
        await vi.advanceTimersByTimeAsync(10_000)
        expect(controller.timerDisplayTarget.textContent).toBe('00:01:00')

        controller.gamePausedRemainingSecondsValue = 0
        controller.gamePausedRemainingSecondsValueChanged()
        expect(controller.timerDisplayTarget.textContent).toBe('00:00:00')
    })
})
