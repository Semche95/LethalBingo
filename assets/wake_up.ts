// Wall clock gap (ms) beyond which the client clock is considered to have
// stalled or jumped between two frames.
export const CLOCK_JUMP_THRESHOLD_MS = 1_000

/**
 * performance.now() stops while the computer sleeps on most systems, while the
 * wall clock keeps running: a gap between the two since the previous frame
 * reveals a sleep (or a system clock change, where a resync is harmless).
 */
export function hasClientClockJumped(previousWallMs: number, previousClientMs: number, wallMs: number, clientMs: number): boolean {
    return Math.abs((wallMs - previousWallMs) - (clientMs - previousClientMs)) > CLOCK_JUMP_THRESHOLD_MS
}

/**
 * Calls onJump on the first frame after a sleep or a system clock change.
 * Date.now() only serves to detect the jump, never to compute the time.
 * Returns the function that stops watching.
 */
export function watchClientClockJumps(onJump: () => void): () => void {
    let previousWallTime = Date.now()
    let previousClientTime = performance.now()

    const tick = () => {
        const wallTime = Date.now()
        const clientTime = performance.now()

        if (hasClientClockJumped(previousWallTime, previousClientTime, wallTime, clientTime)) {
            onJump()
        }

        previousWallTime = wallTime
        previousClientTime = clientTime
        animationFrameId = window.requestAnimationFrame(tick)
    }
    let animationFrameId = window.requestAnimationFrame(tick)

    return () => window.cancelAnimationFrame(animationFrameId)
}

/**
 * Calls onWake when the page may have missed things: after a sleep or a system
 * clock change, when the network comes back and, with `visibility`, when the
 * tab becomes visible again. Returns the function that stops watching.
 */
export function watchWakeUps(onWake: () => void, { visibility = false }: { visibility?: boolean } = {}): () => void {
    const onVisibilityChange = (): void => {
        if (document.visibilityState === 'visible') {
            onWake()
        }
    }

    const stopClockJumpWatch = watchClientClockJumps(onWake)
    window.addEventListener('online', onWake)
    if (visibility) {
        document.addEventListener('visibilitychange', onVisibilityChange)
    }

    return () => {
        stopClockJumpWatch()
        window.removeEventListener('online', onWake)
        document.removeEventListener('visibilitychange', onVisibilityChange)
    }
}
