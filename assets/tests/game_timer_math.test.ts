import { describe, it, expect } from 'vitest'
import {
    calculateElapsedSeconds,
    calculateExactRemainingSeconds,
    calculateRemainingSeconds,
    formatGameDuration,
    formatSeconds,
} from '../controllers/game/game_timer_math'

// Countdown duration of the games in these tests (1 hour).
const GAME_DURATION_SECONDS = 3600

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
    it('returns the whole countdown duration when the game has not started', () => {
        expect(calculateRemainingSeconds({ ...baseParams, gameStartedAt: null, gameCountdownZeroAt: null }, 5400)).toBe(5400)
    })

    it('returns 0 when the game is finished', () => {
        expect(calculateRemainingSeconds({ ...baseParams, gameFinishedAt: startedAtSec + 100 }, GAME_DURATION_SECONDS)).toBe(0)
    })

    it('counts down to the countdown zero moment with a single final ceil', () => {
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + 600_000 }, GAME_DURATION_SECONDS)).toBe(3000)
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + 600_400 }, GAME_DURATION_SECONDS)).toBe(3000)
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + 601_000 }, GAME_DURATION_SECONDS)).toBe(2999)
    })

    it('keeps the sub-second part of the countdown zero moment', () => {
        const params = { ...baseParams, gameCountdownZeroAt: startedAtSec + 60.25 }

        expect(calculateRemainingSeconds({ ...params, currentServerTime: START + 250 }, GAME_DURATION_SECONDS)).toBe(60)
        expect(calculateRemainingSeconds({ ...params, currentServerTime: START + 249 }, GAME_DURATION_SECONDS)).toBe(61)
    })

    it('shows the frozen remaining time during a pause, whatever the time', () => {
        const params = { ...baseParams, gameIsPaused: true, gameCountdownZeroAt: null, gamePausedRemainingSeconds: 2999.7 }

        expect(calculateRemainingSeconds({ ...params, currentServerTime: START }, GAME_DURATION_SECONDS)).toBe(3000)
        expect(calculateRemainingSeconds({ ...params, currentServerTime: START + 86_400_000 }, GAME_DURATION_SECONDS)).toBe(3000)
    })

    it('reaches exactly 0 at the countdown zero moment and never goes negative', () => {
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + GAME_DURATION_SECONDS * 1000 }, GAME_DURATION_SECONDS)).toBe(0)
        expect(calculateRemainingSeconds({ ...baseParams, currentServerTime: START + (GAME_DURATION_SECONDS + 5000) * 1000 }, GAME_DURATION_SECONDS)).toBe(0)
        expect(calculateRemainingSeconds({ ...baseParams, gameIsPaused: true, gameCountdownZeroAt: null, gamePausedRemainingSeconds: -1 }, GAME_DURATION_SECONDS)).toBe(0)
    })
})

describe('calculateExactRemainingSeconds', () => {
    it('is the unrounded remaining time, clamped at zero', () => {
        expect(calculateExactRemainingSeconds({ ...baseParams, currentServerTime: START + 600_250 })).toBeCloseTo(2999.75, 6)
        expect(calculateExactRemainingSeconds({ ...baseParams, currentServerTime: START + 4_000_000 })).toBe(0)
    })
})

describe('formatGameDuration', () => {
    it('shows 00:00:00 when the game is finished regardless of remaining', () => {
        expect(formatGameDuration(startedAtSec + 100, 999)).toBe('00:00:00')
    })

    it('shows 00:00:00 when time is up', () => {
        expect(formatGameDuration(null, 0)).toBe('00:00:00')
    })

    it('formats the remaining time with zero padding', () => {
        expect(formatGameDuration(null, 3000)).toBe('00:50:00')
        expect(formatGameDuration(null, 5400)).toBe('01:30:00')
    })
})

describe('formatSeconds', () => {
    it('formats hours, minutes and seconds with zero padding', () => {
        expect(formatSeconds(0)).toBe('00:00:00')
        expect(formatSeconds(183)).toBe('00:03:03')
        expect(formatSeconds(3601)).toBe('01:00:01')
        expect(formatSeconds(59)).toBe('00:00:59')
    })
})

describe('calculateElapsedSeconds', () => {
    const stopwatchParams = {
        gameStartedAt: startedAtSec as number | null,
        gameFinishedAt: null as number | null,
        gamePausedAt: null as number | null,
        gamePausedDurationSeconds: 0,
        gameIsPaused: false,
        currentServerTime: START,
    }

    it('is 0 before the game starts', () => {
        expect(calculateElapsedSeconds({ ...stopwatchParams, gameStartedAt: null, currentServerTime: START + 50_000 })).toBe(0)
    })

    it('counts the time since the start, rounded down', () => {
        expect(calculateElapsedSeconds({ ...stopwatchParams, currentServerTime: START + 999 })).toBe(0)
        expect(calculateElapsedSeconds({ ...stopwatchParams, currentServerTime: START + 61_500 })).toBe(61)
    })

    it('excludes the finished pauses', () => {
        expect(calculateElapsedSeconds({ ...stopwatchParams, gamePausedDurationSeconds: 30.5, currentServerTime: START + 100_000 })).toBe(69)
    })

    it('stays frozen at the start of the current pause', () => {
        const paused = { ...stopwatchParams, gameIsPaused: true, gamePausedAt: startedAtSec + 40, gamePausedDurationSeconds: 10 }

        expect(calculateElapsedSeconds({ ...paused, currentServerTime: START + 40_000 })).toBe(30)
        expect(calculateElapsedSeconds({ ...paused, currentServerTime: START + 900_000 })).toBe(30)
    })

    it('stops at the end of the game', () => {
        expect(calculateElapsedSeconds({ ...stopwatchParams, gameFinishedAt: startedAtSec + 120, currentServerTime: START + 900_000 })).toBe(120)
    })

    it('never goes negative', () => {
        expect(calculateElapsedSeconds({ ...stopwatchParams, currentServerTime: START - 5_000 })).toBe(0)
    })
})
