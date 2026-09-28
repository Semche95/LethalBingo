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
