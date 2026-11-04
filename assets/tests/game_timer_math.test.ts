import { describe, it, expect } from 'vitest'
import {
    GAME_DURATION_SECONDS,
    calculateExactRemainingSeconds,
    calculateRemainingSeconds,
    formatGameDuration,
} from '../controllers/game/game_timer_math'

// Fixed reference timestamps (ms) for deterministic tests.
const START = 1_000_000_000_000 // game started at this server time (ms)
const startedAtSec = START / 1000

const baseParams = {
    gameStartedAt: startedAtSec,
    gameFinishedAt: null as number | null,
    gameCountdownZeroAt: startedAtSec + GAME_DURATION_SECONDS as number | null,
    gamePausedRemainingSeconds: 0,
    gameIsPaused: false,
    currentServerTime: START,
}

describe('calculateRemainingSeconds', () => {
    it('returns the full default duration when the game has not started', () => {
        expect(calculateRemainingSeconds({ ...baseParams, gameStartedAt: null, gameCountdownZeroAt: null })).toBe(GAME_DURATION_SECONDS)
    })

    it('returns 0 when the game is finished', () => {
        expect(calculateRemainingSeconds({ ...baseParams, gameFinishedAt: startedAtSec + 100 })).toBe(0)
    })

    it('counts down to the countdown zero moment with a single final ceil', () => {
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + 600_000 })).toBe(3000)
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + 600_400 })).toBe(3000)
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + 601_000 })).toBe(2999)
    })

    it('keeps the sub-second part of the countdown zero moment', () => {
        const params = { ...baseParams, gameCountdownZeroAt: startedAtSec + 60.25 }

        expect(calculateRemainingSeconds({ ...params, currentServerTime: START + 250 })).toBe(60)
        expect(calculateRemainingSeconds({ ...params, currentServerTime: START + 249 })).toBe(61)
    })

    it('shows the frozen remaining time during a pause, whatever the time', () => {
        const params = { ...baseParams, gameIsPaused: true, gameCountdownZeroAt: null, gamePausedRemainingSeconds: 2999.7 }

        expect(calculateRemainingSeconds({ ...params, currentServerTime: START })).toBe(3000)
        expect(calculateRemainingSeconds({ ...params, currentServerTime: START + 86_400_000 })).toBe(3000)
    })

    it('reaches exactly 0 at the countdown zero moment and never goes negative', () => {
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + GAME_DURATION_SECONDS * 1000 })).toBe(0)
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + (GAME_DURATION_SECONDS + 5000) * 1000 })).toBe(0)
        expect(calculateRemainingSeconds({ ...baseParams, gameIsPaused: true, gameCountdownZeroAt: null, gamePausedRemainingSeconds: -1 })).toBe(0)
    })
})

describe('calculateExactRemainingSeconds', () => {
    it('is the unrounded remaining time, clamped at zero', () => {
        expect(calculateExactRemainingSeconds({ ...baseParams, currentServerTime: START + 600_250 })).toBeCloseTo(2999.75, 6)
        expect(calculateExactRemainingSeconds({ ...baseParams, currentServerTime: START + 4_000_000 })).toBe(0)
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