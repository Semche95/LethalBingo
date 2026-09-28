import { describe, it, expect } from 'vitest'
import { calculateRemainingSeconds, RemainingSecondsParams } from '../controllers/game/game_timer_math'
import fixtureData from '../../tests/fixtures/game_timer_parity.json'

interface FixtureCase {
    gameStartedAt: number | null
    gameFinishedAt: number | null
    gameTotalPausedSeconds: number
    gameManualAdjustmentSeconds: number
    gameLastPausedAt: number | null
    gameIsPaused: boolean
    currentServerTime: number
    expected: number
}

// The fixture is generated from the REAL production TS function and committed.
// This suite verifies the production TS function still reproduces it exactly,
// and the PHP suite verifies the PHP production function against the same file.
const fixture = fixtureData as FixtureCase[]

describe('game_timer parity fixture (TS production function)', () => {
    it('reproduces the expected value for every committed case', () => {
        expect(fixture.length).toBeGreaterThan(0)

        for (const c of fixture) {
            const params: RemainingSecondsParams = {
                gameStartedAt: c.gameStartedAt,
                gameFinishedAt: c.gameFinishedAt,
                gameTotalPausedSeconds: c.gameTotalPausedSeconds,
                gameManualAdjustmentSeconds: c.gameManualAdjustmentSeconds,
                gameLastPausedAt: c.gameLastPausedAt,
                gameIsPaused: c.gameIsPaused,
                currentServerTime: c.currentServerTime,
            }
            expect(calculateRemainingSeconds(params)).toBe(c.expected)
        }
    })
})