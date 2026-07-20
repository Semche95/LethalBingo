// Pure timer math for the game timer. These functions take explicit parameters
// instead of reading Stimulus controller values, so they can be unit-tested in
// isolation (no DOM required).

// Default game duration in seconds (1 hour) when a game has no start time.
export const GAME_DURATION_SECONDS = 3600

export interface RemainingSecondsParams {
    gameStartedAt: number | null
    gameFinishedAt: number | null
    gameTotalPausedSeconds: number
    gameLastPausedAt: number | null
    gameIsPaused: boolean
    // Current server time in milliseconds.
    currentServerTime: number
}

/**
 * Computes the remaining seconds in the game.
 *
 * - If the game has not started, returns the full default duration.
 * - If the game is finished, returns 0.
 * - Otherwise subtracts elapsed time and all paused time (including the time
 *   elapsed since the current pause started, if the game is paused) from the
 *   default duration, floored to 0.
 */
export function calculateRemainingSeconds(params: RemainingSecondsParams): number {
    const {
        gameStartedAt,
        gameFinishedAt,
        gameTotalPausedSeconds,
        gameLastPausedAt,
        gameIsPaused,
        currentServerTime,
    } = params

    if (!gameStartedAt) {
        return GAME_DURATION_SECONDS
    } else if (gameFinishedAt) {
        return 0
    }

    // Get current server time and game start time in milliseconds
    const serverNow = currentServerTime
    const gameStartedAtMs = gameStartedAt * 1000

    // Calculate elapsed seconds since game started
    const elapsedSeconds = Math.floor((serverNow - gameStartedAtMs) / 1000)

    // Subtract total paused time
    let adjustedElapsedSeconds = elapsedSeconds - gameTotalPausedSeconds

    // If the game is currently paused, subtract the time since it was paused
    if (gameIsPaused && gameLastPausedAt) {
        const lastPausedAtMs = gameLastPausedAt * 1000
        const pausedSeconds = Math.floor((serverNow - lastPausedAtMs) / 1000)
        adjustedElapsedSeconds -= pausedSeconds
    }

    // Calculate remaining seconds (don't go below zero)
    return Math.max(0, GAME_DURATION_SECONDS - adjustedElapsedSeconds)
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

// Checks if the remaining time is at a 10-minute interval (50, 40, 30, 20, 10 minutes)
export function isAtTenMinuteInterval(remainingSeconds: number): boolean {
    // Convert to minutes
    const remainingMinutes = Math.floor(remainingSeconds / 60)
    const remainingSecondsInMinute = remainingSeconds % 60

    // Check if it's exactly at a 10-minute mark (50, 40, 30, 20, 10 minutes)
    // Require exactly 0 seconds to ensure precise timing
    return remainingMinutes > 0 && remainingMinutes <= 50 && remainingMinutes % 10 === 0 && remainingSecondsInMinute === 0
}

// Checks if the remaining time is at 5 minutes (00:05:00)
export function isAtFiveMinutesExactly(remainingSeconds: number): boolean {
    // Convert to minutes
    const remainingMinutes = Math.floor(remainingSeconds / 60)
    const remainingSecondsInMinute = remainingSeconds % 60

    // Check if it's exactly at 5 minutes (00:05:00)
    return remainingMinutes === 5 && remainingSecondsInMinute === 0
}

// Checks if the remaining time is at 3 minutes (00:03:00)
export function isAtThreeMinutesExactly(remainingSeconds: number): boolean {
    // Convert to minutes
    const remainingMinutes = Math.floor(remainingSeconds / 60)
    const remainingSecondsInMinute = remainingSeconds % 60

    // Check if it's exactly at 3 minutes (00:03:00)
    return remainingMinutes === 3 && remainingSecondsInMinute === 0
}

// Checks if the remaining time is exactly 1 minute (00:01:00) or less
export function isOneMinuteOrLess(remainingSeconds: number): boolean {
    // Convert to minutes
    const remainingMinutes = Math.floor(remainingSeconds / 60)
    const remainingSecondsInMinute = remainingSeconds % 60

    // Check if it's exactly 1 minute (00:01:00) or less
    return (remainingMinutes === 1 && remainingSecondsInMinute === 0) || remainingMinutes === 0
}
