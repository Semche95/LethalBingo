// Pure timer math for the game timer. These functions take explicit parameters
// instead of reading Stimulus controller values, so they can be unit-tested in
// isolation (no DOM required).

// Default game duration in seconds (1 hour) when a game has no start time.
export const GAME_DURATION_SECONDS = 3600

export interface RemainingSecondsParams {
    gameStartedAt: number | null
    gameFinishedAt: number | null
    gameTotalPausedSeconds: number
    gameManualAdjustmentSeconds: number
    gameLastPausedAt: number | null
    gameIsPaused: boolean
    // Current server time in milliseconds.
    currentServerTime: number
}

/**
 * Computes the remaining seconds in the game. Mirror of
 * GameTimerService::getRemainingSeconds() on the PHP side.
 */
export function calculateRemainingSeconds(params: RemainingSecondsParams): number {
    const {
        gameStartedAt,
        gameFinishedAt,
        gameTotalPausedSeconds,
        gameManualAdjustmentSeconds,
        gameLastPausedAt,
        gameIsPaused,
        currentServerTime,
    } = params

    if (!gameStartedAt) {
        return GAME_DURATION_SECONDS
    } else if (gameFinishedAt) {
        return 0
    }

    // While paused, use lastPausedAt directly instead of subtracting the
    // current pause from now: the float residue would make the ceil flicker.
    const endMs = gameIsPaused && gameLastPausedAt ? gameLastPausedAt * 1000 : currentServerTime
    const elapsedSeconds = (endMs - gameStartedAt * 1000) / 1000 - gameTotalPausedSeconds
    const remainingSeconds = GAME_DURATION_SECONDS - elapsedSeconds + gameManualAdjustmentSeconds

    return Math.max(0, Math.ceil(remainingSeconds))
}

/**
 * Formats a number of remaining seconds as HH:MM:SS.
 * Returns '01:00:00' if the game has not started and '00:00:00' if finished
 * or when time is up (remainingSeconds <= 0).
 */
export function formatGameDuration(gameStartedAt: number | null, gameFinishedAt: number | null, remainingSeconds: number): string {
    if (!gameStartedAt) {
        return '01:00:00'
    } else if (gameFinishedAt) {
        return '00:00:00'
    }

    // If time is up, return 00:00:00
    if (remainingSeconds <= 0) {
        return '00:00:00'
    }

    // Convert remaining seconds to hours, minutes, seconds
    const hours = Math.floor(remainingSeconds / 3600)
    const minutes = Math.floor((remainingSeconds % 3600) / 60)
    const seconds = remainingSeconds % 60

    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
}