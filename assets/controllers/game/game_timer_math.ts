// Pure timer math for the game timer. These functions take explicit parameters
// instead of reading Stimulus controller values, so they can be unit-tested in
// isolation (no DOM required).

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

// Stopwatch of a game without time limit (docs/TIMER.md section 8).
export interface ElapsedSecondsParams {
    gameStartedAt: number | null
    gameFinishedAt: number | null
    // Start of the current pause (fractional seconds), null outside a pause.
    gamePausedAt: number | null
    // Total of the finished pauses.
    gamePausedDurationSeconds: number
    gameIsPaused: boolean
    // Current server time in milliseconds.
    currentServerTime: number
}

/**
 * Computes the remaining seconds of a countdown, the whole countdown duration
 * before the game starts.
 */
export function calculateRemainingSeconds(params: RemainingSecondsParams, countdownDurationSeconds: number): number {
    if (!params.gameStartedAt) {
        return countdownDurationSeconds
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
 * Elapsed seconds of a stopwatch, pauses excluded, rounded down and never
 * negative. Frozen at the start of the current pause, or at the end of the game.
 */
export function calculateElapsedSeconds(params: ElapsedSecondsParams): number {
    const { gameStartedAt, gameFinishedAt, gamePausedAt, gamePausedDurationSeconds, gameIsPaused, currentServerTime } = params

    if (!gameStartedAt) {
        return 0
    }

    let endMs = currentServerTime
    if (gameIsPaused && gamePausedAt) {
        endMs = gamePausedAt * 1000
    } else if (gameFinishedAt) {
        endMs = gameFinishedAt * 1000
    }

    return Math.max(0, Math.floor((endMs - gameStartedAt * 1000) / 1000 - gamePausedDurationSeconds))
}

/**
 * Formats the remaining seconds of a countdown as HH:MM:SS: '00:00:00' once the
 * game is finished or the time is up.
 */
export function formatGameDuration(gameFinishedAt: number | null, remainingSeconds: number): string {
    if (gameFinishedAt || remainingSeconds <= 0) {
        return '00:00:00'
    }

    return formatSeconds(remainingSeconds)
}

/**
 * Formats a whole number of seconds as HH:MM:SS.
 */
export function formatSeconds(totalSeconds: number): string {
    const hours = Math.floor(totalSeconds / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)
    const seconds = totalSeconds % 60

    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
}