import { describe, it, expect } from 'vitest'
import {
    GAME_DURATION_SECONDS,
    calculateRemainingSeconds,
    formatGameDuration,
} from '../controllers/game/game_timer_math'

// Fixed reference timestamps (ms) for deterministic tests.
const START = 1_000_000_000_000 // game started at this server time (ms)
const startedAtSec = START / 1000

const baseParams = {
    gameStartedAt: startedAtSec,
    gameFinishedAt: null as number | null,
    gameTotalPausedSeconds: 0,
    gameManualAdjustmentSeconds: 0,
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

    it('floors elapsed time to whole seconds via the single final ceil', () => {
        // 600.4s elapsed -> 3600 - 600.4 = 2999.6 -> ceil = 3000s remaining
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            currentServerTime: START + 600_400,
        })
        expect(remaining).toBe(3000)
    })

    it('steps down one second past the whole-second mark', () => {
        // 601s elapsed -> 2999s remaining
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            currentServerTime: START + 601_000,
        })
        expect(remaining).toBe(2999)
    })

    it('subtracts the total previously paused time', () => {
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            gameTotalPausedSeconds: 120,
            currentServerTime: START + 600_000,
        })
        expect(remaining).toBe(3120)
    })

    it('subtracts the in-progress pause time when currently paused', () => {
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            gameIsPaused: true,
            gameLastPausedAt: startedAtSec + 300,
            currentServerTime: START + 900_000,
        })
        expect(remaining).toBe(3300)
    })

    it('adds a positive manual adjustment to the remaining time', () => {
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            gameManualAdjustmentSeconds: 60,
            currentServerTime: START + 600_000,
        })
        expect(remaining).toBe(3060)
    })

    it('reduces remaining time with a negative manual adjustment', () => {
        const remaining = calculateRemainingSeconds({
            ...baseParams,
            gameManualAdjustmentSeconds: -60,
            currentServerTime: START + 600_000,
        })
        expect(remaining).toBe(2940)
    })

    it('after absorption of the overtime deficit, +1 minute lands on exactly 60 and keeps counting down', () => {
        // Overtime deficit absorbed into paused time (300s), then a +1 minute
        // applied on top: the countdown must start at exactly 60 and keep
        // decreasing as the simulated clock advances with no further adjustment.
        const t0 = START + (GAME_DURATION_SECONDS + 300) * 1000
        const r0 = calculateRemainingSeconds({
            ...baseParams,
            gameManualAdjustmentSeconds: 60,
            gameTotalPausedSeconds: 300,
            currentServerTime: t0,
        })
        expect(r0).toBe(60)

        const r1 = calculateRemainingSeconds({
            ...baseParams,
            gameManualAdjustmentSeconds: 60,
            gameTotalPausedSeconds: 300,
            currentServerTime: t0 + 10_000,
        })
        expect(r1).toBe(50)
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