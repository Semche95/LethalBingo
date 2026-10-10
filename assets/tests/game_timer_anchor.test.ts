import { describe, it, expect } from 'vitest'
import {
    estimateServerTimeFromResponses,
    estimateServerTimeFromRoundTrip,
    parseServerTimeHeader,
    SERVER_TIME_METRIC,
    type ResponseTimingEntry,
} from '../controllers/game/game_timer_anchor'
import { hasClientClockJumped } from '../wake_up'

const SENT_AT = 1_700_000_000.25 // server send time (seconds)

function response(responseStart: number, sentAt: number | string | null = SENT_AT): ResponseTimingEntry {
    return {
        responseStart,
        serverTiming: sentAt === null ? [] : [{ name: SERVER_TIME_METRIC, description: String(sentAt) }],
    }
}

describe('estimateServerTimeFromResponses', () => {
    it('extrapolates the send time by the client time elapsed since the response arrived', () => {
        expect(estimateServerTimeFromResponses([response(1_000)], 1_600)).toBe(SENT_AT * 1000 + 600)
    })

    it('keeps the highest estimate: the response with the smallest network delay wins', () => {
        const entries = [
            // Sent at SENT_AT, arrived at 1_000 (slow response).
            response(1_000),
            // Sent 500ms later, arrived only 400ms later: 100ms faster.
            response(1_400, SENT_AT + 0.5),
        ]

        expect(estimateServerTimeFromResponses(entries, 2_000)).toBe(SENT_AT * 1000 + 500 + 600)
    })

    it('ignores responses without the server time metric', () => {
        const entries = [
            { responseStart: 1_000, serverTiming: [{ name: 'db', description: '' }] },
            response(1_000, null),
            response(1_000, 'not-a-number'),
        ]

        expect(estimateServerTimeFromResponses(entries, 2_000)).toBeNull()
    })

    it('ignores entries whose timing is withheld (responseStart 0) or not reached yet', () => {
        expect(estimateServerTimeFromResponses([response(0), response(3_000)], 2_000)).toBeNull()
    })

    it('returns null without any entry', () => {
        expect(estimateServerTimeFromResponses([], 2_000)).toBeNull()
    })
})

describe('parseServerTimeHeader', () => {
    it('reads the send time among other metrics', () => {
        expect(parseServerTimeHeader(`db;dur=12, ${SERVER_TIME_METRIC};desc="${SENT_AT}"`)).toBe(SENT_AT)
    })

    it('returns null without the metric or with an invalid value', () => {
        expect(parseServerTimeHeader(null)).toBeNull()
        expect(parseServerTimeHeader('db;dur=12')).toBeNull()
        expect(parseServerTimeHeader(`${SERVER_TIME_METRIC};desc="not-a-number"`)).toBeNull()
    })
})

describe('estimateServerTimeFromRoundTrip', () => {
    // Request left at 1_000, response arrived at 1_100, read at 1_150.
    const lowerBound = SENT_AT * 1000 + 50
    const upperBound = SENT_AT * 1000 + 150

    it('moves a lagging estimate forward to the lower bound', () => {
        expect(estimateServerTimeFromRoundTrip(lowerBound - 60_000, SENT_AT, 1_000, 1_100, 1_150)).toBe(lowerBound)
    })

    it('keeps an estimate already within the round trip bounds', () => {
        expect(estimateServerTimeFromRoundTrip(lowerBound + 40, SENT_AT, 1_000, 1_100, 1_150)).toBe(lowerBound + 40)
    })

    it('moves an estimate proven ahead back to the lower bound', () => {
        expect(estimateServerTimeFromRoundTrip(upperBound + 1, SENT_AT, 1_000, 1_100, 1_150)).toBe(lowerBound)
    })
})

describe('hasClientClockJumped', () => {
    it('detects a client clock that stalled while the wall clock ran (sleep)', () => {
        expect(hasClientClockJumped(0, 0, 60_000, 16)).toBe(true)
    })

    it('ignores ordinary frames', () => {
        expect(hasClientClockJumped(0, 0, 16, 17)).toBe(false)
        expect(hasClientClockJumped(0, 0, 900, 0)).toBe(false)
    })
})
