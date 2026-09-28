import { describe, it, expect } from 'vitest'
import { estimateServerTimeFromResponses, SERVER_TIME_METRIC, type ResponseTimingEntry } from '../controllers/game/game_timer_anchor'

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
