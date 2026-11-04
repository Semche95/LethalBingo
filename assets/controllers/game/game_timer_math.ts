// Pure timer math for the game timer. These functions take explicit parameters
// instead of reading Stimulus controller values, so they can be unit-tested in
// isolation (no DOM required).

// Default game duration in seconds (1 hour) when a game has no start time.
export const GAME_DURATION_SECONDS = 3600

export interface RemainingSecondsParams {
    gameStartedAt: number | null
    gameFinishedAt: number | null
    // Moment the countdown reaches zero (fractional seconds), null during a pause.
    gameCountdownZeroAt: number | null
    // Remaining time frozen during a pause.
    gamePausedRemainingSeconds: number
    gameIsPaused: boolean
    // Current server time in milliseconds.
    currentServerTime: number
}

/**
 * Computes the remaining seconds in the game.
 */
export function calculateRemainingSeconds(params: RemainingSecondsParams): number {
    if (!params.gameStartedAt) {
        return GAME_DURATION_SECONDS
    } else if (params.gameFinishedAt) {
        return 0
    }

    return Math.ceil(calculateExactRemainingSeconds(params))
}

/**
 * Unrounded remaining seconds of a started game, never negative. Used to
 * schedule the threshold sounds to the millisecond.
 */
export function calculateExactRemainingSeconds(params: RemainingSecondsParams): number {
    const { gameCountdownZeroAt, gamePausedRemainingSeconds, gameIsPaused, currentServerTime } = params

    if (gameIsPaused) {
        return Math.max(0, gamePausedRemainingSeconds)
    } else if (!gameCountdownZeroAt) {
        return 0
    }

    return Math.max(0, (gameCountdownZeroAt * 1000 - currentServerTime) / 1000)
}

/**
 * Whether the countdown of a game in progress has reached zero. Mirror of
 * GameTimerService::isTimeUp() on the PHP side, which refuses the pause then.
 */
export function isTimeUp(params: RemainingSecondsParams): boolean {
    if (!params.gameStartedAt || params.gameFinishedAt) {
        return false
    }

    return calculateExactRemainingSeconds(params) <= 0
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