// Name of the Server-Timing metric stamped on every response by
// App\EventListener\ServerTimeHeaderListener (server send time, seconds).
export const SERVER_TIME_METRIC = 'app-time'

// Subset of PerformanceResourceTiming used to read the stamped responses.
export type ResponseTimingEntry = Pick<PerformanceResourceTiming, 'responseStart'> & {
    serverTiming: ReadonlyArray<Pick<PerformanceServerTiming, 'name' | 'description'>>
}

/**
 * Estimates the current server time (ms) from the responses the browser
 * received: each stamped response was sent at a known server time and arrived
 * at responseStart, so sentAt + (nowMs - responseStart) can only lag behind the
 * real server time (by that response's network delay). The best estimate is
 * therefore the highest one. Returns null when no stamped response is known.
 */
export function estimateServerTimeFromResponses(entries: ResponseTimingEntry[], nowMs: number): number | null {
    let best: number | null = null

    for (const entry of entries) {
        // responseStart is 0 when the browser withholds the timing.
        if (entry.responseStart <= 0 || entry.responseStart > nowMs) {
            continue
        }

        const metric = entry.serverTiming.find(timing => timing.name === SERVER_TIME_METRIC)
        const sentAtSeconds = metric ? Number.parseFloat(metric.description) : Number.NaN
        if (!Number.isFinite(sentAtSeconds)) {
            continue
        }

        const estimate = sentAtSeconds * 1000 + (nowMs - entry.responseStart)
        if (best === null || estimate > best) {
            best = estimate
        }
    }

    return best
}

/**
 * Reads the server send time (seconds) from a Server-Timing header value, as
 * returned by Headers.get() (several metrics may be joined by commas).
 */
export function parseServerTimeHeader(header: string | null): number | null {
    if (header === null) {
        return null
    }

    for (const metric of header.split(',')) {
        const [name, ...params] = metric.split(';').map(part => part.trim())
        if (name !== SERVER_TIME_METRIC) {
            continue
        }

        const desc = params.find(param => param.startsWith('desc='))
        const sentAtSeconds = desc ? Number.parseFloat(desc.slice('desc='.length).replaceAll('"', '')) : Number.NaN

        return Number.isFinite(sentAtSeconds) ? sentAtSeconds : null
    }

    return null
}

/**
 * Re-anchors on a request made by the client itself (ms). The server sent its
 * time after the request left and before the response arrived, so the real
 * server time now lies between sentAt + (now - arrival) and
 * sentAt + (now - requestStart). An estimate above that upper bound proves the
 * client clock ran fast (the only case where the anchor may move backward);
 * otherwise the highest estimate wins as usual.
 */
export function estimateServerTimeFromRoundTrip(
    currentEstimateMs: number,
    sentAtSeconds: number,
    requestStartMs: number,
    responseArrivalMs: number,
    nowMs: number,
): number {
    const lowerBound = sentAtSeconds * 1000 + (nowMs - responseArrivalMs)
    const upperBound = sentAtSeconds * 1000 + (nowMs - requestStartMs)

    if (currentEstimateMs > upperBound) {
        return lowerBound
    }

    return Math.max(currentEstimateMs, lowerBound)
}

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
