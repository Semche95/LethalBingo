import { describe, it, expect } from 'vitest'
import {
    computeAlertColor,
    createThresholdState,
    evaluateThresholds,
    listUpcomingAlerts,
} from '../controllers/game/game_timer_thresholds'

const DURATION = 3600 // 1 hour

// ---------------------------------------------------------------------------
// Colour: pure function of the current remaining time.
// ---------------------------------------------------------------------------

describe('computeAlertColor', () => {
    it('returns warning for each remaining value within 5s strictly below a 10-minute multiple', () => {
        // Just above / exactly at / just below / within the window around 00:50:00.
        expect(computeAlertColor(3001, DURATION)).toBeNull()
        expect(computeAlertColor(3000, DURATION)).toBeNull()
        expect(computeAlertColor(2999, DURATION)).toBe('warning')
        expect(computeAlertColor(2996, DURATION)).toBe('warning')
        expect(computeAlertColor(2995, DURATION)).toBe('warning')
        expect(computeAlertColor(2994, DURATION)).toBeNull()
    })

    it('applies the warning rule to any 10-minute multiple (arbitrary duration)', () => {
        // Around 00:30:00 for a 1h game.
        expect(computeAlertColor(1801, DURATION)).toBeNull()
        expect(computeAlertColor(1800, DURATION)).toBeNull()
        expect(computeAlertColor(1799, DURATION)).toBe('warning')
        expect(computeAlertColor(1796, DURATION)).toBe('warning')
        expect(computeAlertColor(1795, DURATION)).toBe('warning')
        expect(computeAlertColor(1794, DURATION)).toBeNull()
    })

    it('does not warn near the full-duration top boundary at the very start', () => {
        // 00:59:56..00:59:59 are within 5s below 01:00:00, but that is the full
        // duration (start of game): no warning.
        expect(computeAlertColor(3599, DURATION)).toBeNull()
        expect(computeAlertColor(3595, DURATION)).toBeNull()
    })

    it('returns danger for values within 5s strictly below 5 minutes', () => {
        expect(computeAlertColor(301, DURATION)).toBeNull()
        expect(computeAlertColor(300, DURATION)).toBeNull()
        expect(computeAlertColor(299, DURATION)).toBe('danger')
        expect(computeAlertColor(296, DURATION)).toBe('danger')
        expect(computeAlertColor(295, DURATION)).toBe('danger')
        expect(computeAlertColor(294, DURATION)).toBeNull()
    })

    it('returns danger for values within 5s strictly below 3 minutes', () => {
        expect(computeAlertColor(181, DURATION)).toBeNull()
        expect(computeAlertColor(180, DURATION)).toBeNull()
        expect(computeAlertColor(179, DURATION)).toBe('danger')
        expect(computeAlertColor(176, DURATION)).toBe('danger')
        expect(computeAlertColor(175, DURATION)).toBe('danger')
        expect(computeAlertColor(174, DURATION)).toBeNull()
    })

    it('returns continuous danger for every value below 60s, including zero and overtime', () => {
        expect(computeAlertColor(60, DURATION)).toBeNull()
        expect(computeAlertColor(59, DURATION)).toBe('danger')
        expect(computeAlertColor(30, DURATION)).toBe('danger')
        expect(computeAlertColor(1, DURATION)).toBe('danger')
        expect(computeAlertColor(0, DURATION)).toBe('danger')
        expect(computeAlertColor(-1, DURATION)).toBe('danger')
        expect(computeAlertColor(-300, DURATION)).toBe('danger')
    })

    it('returns no colour for values far from any threshold', () => {
        expect(computeAlertColor(1500, DURATION)).toBeNull()
        expect(computeAlertColor(2400, DURATION)).toBeNull()
        expect(computeAlertColor(999, DURATION)).toBeNull()
    })
})

// ---------------------------------------------------------------------------
// Sound: one-shot event, fired once per threshold crossing.
// ---------------------------------------------------------------------------

describe('evaluateThresholds (sound)', () => {
    it('fires no sound at the start of the game', () => {
        const state = createThresholdState()
        expect(evaluateThresholds(3600, DURATION, state)).toBeNull() // first frame, no prev
        expect(evaluateThresholds(3599, DURATION, state)).toBeNull() // below full duration
    })

    it('fires a warning when crossing below each 10-minute mark', () => {
        const state = createThresholdState()
        const boundaryTicks: Array<[number, number]> = [
            [3001, 2999], // cross below 50:00
            [2401, 2399], // cross below 40:00
            [1801, 1799], // cross below 30:00
            [1201, 1199], // cross below 20:00
            [601, 599],   // cross below 10:00
        ]

        for (const [prev, remaining] of boundaryTicks) {
            evaluateThresholds(prev, DURATION, state) // seed the previous value
            expect(evaluateThresholds(remaining, DURATION, state)).toBe('warning')
        }
    })

    it('fires a warning on a jump that skips the exact threshold value', () => {
        const state = createThresholdState()
        evaluateThresholds(3001, DURATION, state)
        expect(evaluateThresholds(2999, DURATION, state)).toBe('warning')
    })

    it('re-fires a warning when the same threshold is legitimately crossed again', () => {
        const state = createThresholdState()
        evaluateThresholds(3001, DURATION, state)
        expect(evaluateThresholds(2999, DURATION, state)).toBe('warning')

        // +1 min pushes remaining back above 50:00, then it comes back down:
        // this is a fresh downward crossing, so the sound must fire again.
        evaluateThresholds(3059, DURATION, state)
        expect(evaluateThresholds(2999, DURATION, state)).toBe('warning')
    })

    it('fires a danger sound at 5, 3 and 1 minutes remaining', () => {
        const state = createThresholdState()
        const ticks: Array<[number, number]> = [
            [301, 299], // cross below 5:00
            [181, 179], // cross below 3:00
            [61, 59],   // cross below 1:00
        ]

        for (const [prev, remaining] of ticks) {
            evaluateThresholds(prev, DURATION, state) // seed the previous value
            expect(evaluateThresholds(remaining, DURATION, state)).toBe('danger')
        }
    })

    it('ignores upward crossings entirely', () => {
        const state = createThresholdState()
        evaluateThresholds(100, DURATION, state)
        expect(evaluateThresholds(200, DURATION, state)).toBeNull()
    })

    // -----------------------------------------------------------------------
    // canTriggerSound: manual mutations must stay silent (spec section 5).
    // -----------------------------------------------------------------------

    it('returns null when canTriggerSound is false, even if a crossing is detected', () => {
        const state = createThresholdState()
        evaluateThresholds(301, DURATION, state) // seed just above 5:00
        // Crossing below 5:00 but with canTriggerSound=false → silent.
        expect(evaluateThresholds(299, DURATION, state, false)).toBeNull()
    })

    it('resyncs state even when canTriggerSound is false', () => {
        const state = createThresholdState()
        evaluateThresholds(301, DURATION, state) // seed just above 5:00

        // Silent evaluation: crosses below 5:00 but canTriggerSound=false.
        evaluateThresholds(299, DURATION, state, false)

        // The reference was resynced to 299. Moving back above then down again
        // is a fresh crossing, so it must fire when canTriggerSound is true.
        evaluateThresholds(360, DURATION, state) // back above 5:00
        expect(evaluateThresholds(299, DURATION, state)).toBe('danger')
    })

    it('chains: silent manual crossing through multiple thresholds → natural crossing fires', () => {
        const state = createThresholdState()

        // Start above 30:00.
        evaluateThresholds(1860, DURATION, state)

        // Manual −10 minutes crosses below 30:00 silently.
        evaluateThresholds(1260, DURATION, state, false)
        // Another manual −5 minutes crosses below 10:00 silently.
        evaluateThresholds(599, DURATION, state, false)

        // Manual +1 minute pushes back above 10:00.
        evaluateThresholds(660, DURATION, state, false)

        // Natural countdown crosses below 10:00 → must fire.
        expect(evaluateThresholds(599, DURATION, state)).toBe('warning')
    })

    it('fires the end sound once when the countdown reaches zero', () => {
        const state = createThresholdState()
        evaluateThresholds(2, DURATION, state)
        expect(evaluateThresholds(1, DURATION, state)).toBeNull()
        expect(evaluateThresholds(0, DURATION, state)).toBe('end')

        // Staying at zero (clamped overtime) is not a new crossing.
        expect(evaluateThresholds(0, DURATION, state)).toBeNull()
    })

    it('gives the end sound precedence over other thresholds crossed in the same evaluation', () => {
        const state = createThresholdState()
        evaluateThresholds(61, DURATION, state)

        // e.g. the tab was hidden: 1 minute and zero are crossed at once.
        expect(evaluateThresholds(0, DURATION, state)).toBe('end')
    })

    it('keeps the end sound silent on a manual adjustment reaching zero', () => {
        const state = createThresholdState()
        evaluateThresholds(30, DURATION, state)

        expect(evaluateThresholds(0, DURATION, state, false)).toBeNull()
    })

    it('chains: silent manual zero → +1 minute → natural zero fires the end sound', () => {
        const state = createThresholdState()
        evaluateThresholds(30, DURATION, state)

        // Manual −1 minute reaches zero silently.
        evaluateThresholds(0, DURATION, state, false)
        // Manual +1 minute (after absorption) restores 00:01:00.
        evaluateThresholds(60, DURATION, state, false)

        // Natural countdown crosses 1 minute, then reaches zero.
        expect(evaluateThresholds(59, DURATION, state)).toBe('danger')
        expect(evaluateThresholds(1, DURATION, state)).toBeNull()
        expect(evaluateThresholds(0, DURATION, state)).toBe('end')
    })
})
describe('listUpcomingAlerts', () => {
    it('lists every remaining alert in order, at the moment the display drops below each threshold', () => {
        // 00:10:00.5 remaining: 10:00 is shown until 599 s are left.
        expect(listUpcomingAlerts(600.5, DURATION)).toEqual([
            { alert: 'warning', inSeconds: 1.5 },
            { alert: 'danger', inSeconds: 301.5 },
            { alert: 'danger', inSeconds: 421.5 },
            { alert: 'danger', inSeconds: 541.5 },
            { alert: 'end', inSeconds: 600.5 },
        ])
    })

    it('skips the full duration and the thresholds already crossed', () => {
        const alerts = listUpcomingAlerts(3600, DURATION)

        expect(alerts.filter(({ alert }) => alert === 'warning').map(({ inSeconds }) => inSeconds))
            .toEqual([601, 1201, 1801, 2401, 3001])
        expect(listUpcomingAlerts(0, DURATION)).toEqual([])
    })

    it('agrees with evaluateThresholds on when each alert fires', () => {
        const exactRemaining = 3600
        const state = createThresholdState()
        const fired: Array<{ alert: string, inSeconds: number }> = []

        for (let elapsed = 0; elapsed <= 3601; elapsed += 0.25) {
            const alert = evaluateThresholds(Math.max(0, Math.ceil(exactRemaining - elapsed)), DURATION, state)
            if (alert) {
                fired.push({ alert, inSeconds: elapsed })
            }
        }

        expect(fired).toEqual(listUpcomingAlerts(exactRemaining, DURATION))
    })
})
