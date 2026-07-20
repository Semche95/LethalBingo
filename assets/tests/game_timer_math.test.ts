import { describe, it, expect } from 'vitest'
import {
    GAME_DURATION_SECONDS,
    calculateRemainingSeconds,
    formatGameDuration,
    isAtTenMinuteInterval,
    isAtFiveMinutesExactly,
    isAtThreeMinutesExactly,
    isOneMinuteOrLess,
} from '../controllers/game/game_timer_math'

// Fixed reference timestamps (ms) for deterministic tests.
const START = 1_000_000_000_000 // game started at this server time (ms)
const startedAtSec = START / 1000

const baseParams = {
    gameStartedAt: startedAtSec,
    gameFinishedAt: null as number | null,
    gameTotalPausedSeconds: 0,
    gameLastPausedAt: null as number | null,
    gameIsPaused: false,
    currentServerTime: START,
}

describe('calculateRemainingSeconds', () => {
    it('returns the full default duration when the game has not started', () => {
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            gameStartedAt: null,
        })
        expect(remaining).toBe(GAME_DURATION_SECONDS)
    })

    it('returns 0 when the game is finished', () => {
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            gameFinishedAt: (START / 1000) + 100,
        })
        expect(remaining).toBe(0)
    })

    it('counts down linearly from the start time (no pause)', () => {
        // 600s (10 min) elapsed -> 3600 - 600 = 3000s remaining
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            currentServerTime: START + 600_000,
        })
        expect(remaining).toBe(3000)
    })

    it('floors elapsed time to whole seconds', () => {
        // 600.4s elapsed -> floored to 600s -> 3000s remaining
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            currentServerTime: START + 600_400,
        })
        expect(remaining).toBe(3000)
    })

    it('subtracts the total previously paused time', () => {
        // 600s elapsed, but 120s were paused before -> 3600 - (600-120) = 3120
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            gameTotalPausedSeconds: 120,
            currentServerTime: START + 600_000,
        })
        expect(remaining).toBe(3120)
    })

    it('subtracts the in-progress pause time when currently paused', () => {
        // Game started, paused at START+300s. Now 900s elapsed total.
        // elapsed=900, minus totalPaused(seconds before this pause)=0,
        // minus currentPause(600s) -> 3600 - (900-600) = 3300
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            gameIsPaused: true,
            gameLastPausedAt: startedAtSec + 300,
            currentServerTime: START + 900_000,
        })
        expect(remaining).toBe(3300)
    })

    it('floors remaining seconds to 0 (never negative)', () => {
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            currentServerTime: START + (GAME_DURATION_SECONDS + 5000) * 1000,
        })
        expect(remaining).toBe(0)
    })

    it('reaches exactly 0 at the boundary', () => {
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            currentServerTime: START + GAME_DURATION_SECONDS * 1000,
        })
        expect(remaining).toBe(0)
    })
})

describe('formatGameDuration', () => {
    it('shows the full duration when the game has not started', () => {
        expect(formatGameDuration(null, null, 123)).toBe('01:00:00')
    })

    it('shows 00:00:00 when the game is finished regardless of remaining', () => {
        expect(formatGameDuration(startedAtSec, startedAtSec + 100, 999)).toBe('00:00:00')
    })

    it('shows 00:00:00 when time is up', () => {
        expect(formatGameDuration(startedAtSec, null, 0)).toBe('00:00:00')
    })

    it('formats hours, minutes and seconds with zero padding', () => {
        expect(formatGameDuration(startedAtSec, null, 3000)).toBe('00:50:00')
        expect(formatGameDuration(startedAtSec, null, 183)).toBe('00:03:03')
        expect(formatGameDuration(startedAtSec, null, 3601)).toBe('01:00:01')
        expect(formatGameDuration(startedAtSec, null, 59)).toBe('00:00:59')
    })
})

describe('isAtTenMinuteInterval', () => {
    it('is true exactly at 10,20,30,40,50 minutes remaining', () => {
        expect(isAtTenMinuteInterval(600)).toBe(true)   // 10 min
        expect(isAtTenMinuteInterval(1200)).toBe(true)  // 20 min
        expect(isAtTenMinuteInterval(3000)).toBe(true)  // 50 min
    })

    it('is false just before and just after a 10-minute boundary', () => {
        expect(isAtTenMinuteInterval(599)).toBe(false)
        expect(isAtTenMinuteInterval(601)).toBe(false)
        expect(isAtTenMinuteInterval(1199)).toBe(false)
        expect(isAtTenMinuteInterval(1201)).toBe(false)
    })

    it('is false at 60 minutes (full duration, not a 10-min mark <= 50)', () => {
        expect(isAtTenMinuteInterval(3600)).toBe(false)
    })

    it('is false when seconds are not zero', () => {
        expect(isAtTenMinuteInterval(600 + 1)).toBe(false)
    })

    it('is false at zero (boundary excluded)', () => {
        expect(isAtTenMinuteInterval(0)).toBe(false)
    })
})

describe('isAtFiveMinutesExactly', () => {
    it('is true exactly at 5 minutes', () => {
        expect(isAtFiveMinutesExactly(300)).toBe(true)
    })

    it('is false just before and just after', () => {
        expect(isAtFiveMinutesExactly(299)).toBe(false)
        expect(isAtFiveMinutesExactly(301)).toBe(false)
    })

    it('is false when seconds are not zero', () => {
        expect(isAtFiveMinutesExactly(300 + 5)).toBe(false)
    })
})

describe('isAtThreeMinutesExactly', () => {
    it('is true exactly at 3 minutes', () => {
        expect(isAtThreeMinutesExactly(180)).toBe(true)
    })

    it('is false just before and just after', () => {
        expect(isAtThreeMinutesExactly(179)).toBe(false)
        expect(isAtThreeMinutesExactly(181)).toBe(false)
    })

    it('is false when seconds are not zero', () => {
        expect(isAtThreeMinutesExactly(180 + 10)).toBe(false)
    })
})

describe('isOneMinuteOrLess', () => {
    it('is true exactly at 1 minute', () => {
        expect(isOneMinuteOrLess(60)).toBe(true)
    })

    it('is true for any value under 1 minute', () => {
        expect(isOneMinuteOrLess(59)).toBe(true)
        expect(isOneMinuteOrLess(1)).toBe(true)
        expect(isOneMinuteOrLess(0)).toBe(true)
    })

    it('is false just above 1 minute', () => {
        expect(isOneMinuteOrLess(61)).toBe(false)
        expect(isOneMinuteOrLess(120)).toBe(false)
    })

    it('is false when seconds are not zero at the 1-minute mark', () => {
        expect(isOneMinuteOrLess(61)).toBe(false)
        expect(isOneMinuteOrLess(60 + 1)).toBe(false)
    })
})
