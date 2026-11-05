import { describe, it, expect, vi, afterEach } from 'vitest'
import { SERVER_TIME, START_SEC, buildElement, connectController, connectWithAnchor, useTimerControllerApp } from './game_timer_controller_setup'

describe('game_timer_controller server sync', () => {
    useTimerControllerApp()

    describe('server time anchor', () => {
        // Simulates a response stamped with the server send time that arrived
        // arrivedMsAgo milliseconds before now.
        function stubStampedResponse(sentAtSeconds: number, arrivedMsAgo: number) {
            const entry = {
                responseStart: performance.now() - arrivedMsAgo,
                serverTiming: [{ name: 'app-time', description: String(sentAtSeconds) }],
            }
            Object.defineProperty(performance, 'getEntriesByType', {
                value: (type: string) => type === 'navigation' ? [entry] : [],
                configurable: true,
            })
        }

        function estimatedServerTimeMs(controller: any): number {
            return controller.getCurrentServerTime()
        }

        it('anchors on the stamped response when the rendered serverTime is older', async () => {
            await vi.advanceTimersByTimeAsync(5_000)
            // Rendered at SERVER_TIME, sent 70ms later, arrived 1.5s ago.
            stubStampedResponse(SERVER_TIME + 0.07, 1_500)

            const controller = await connectController(buildElement({ gameStartedAt: START_SEC, serverTime: SERVER_TIME }))

            expect(estimatedServerTimeMs(controller)).toBeCloseTo(SERVER_TIME * 1000 + 70 + 1_500, 3)
        })

        it('falls back to the rendered serverTime without any stamped response', async () => {
            await vi.advanceTimersByTimeAsync(5_000)
            Object.defineProperty(performance, 'getEntriesByType', { value: () => [], configurable: true })

            const controller = await connectController(buildElement({ gameStartedAt: START_SEC, serverTime: SERVER_TIME }))

            expect(estimatedServerTimeMs(controller)).toBeCloseTo(SERVER_TIME * 1000, 3)
        })
    })

    describe('server resync', () => {
        const SERVER_TIME_URL = '/server-time'

        function stubServerTimeResponse(sentAtSeconds: number | null) {
            const headers = new Headers(sentAtSeconds === null ? {} : { 'Server-Timing': `app-time;desc="${sentAtSeconds}"` })
            const fetchMock = vi.fn().mockResolvedValue({ headers })
            vi.stubGlobal('fetch', fetchMock)
            return fetchMock
        }

        async function flushPromises() {
            for (let i = 0; i < 10; i++) {
                await Promise.resolve()
            }
        }

        function showTab() {
            Object.defineProperty(document, 'visibilityState', { value: 'visible', configurable: true })
            document.dispatchEvent(new Event('visibilitychange'))
        }

        afterEach(() => {
            vi.unstubAllGlobals()
        })

        it('catches up on the server time after a sleep once the tab is visible again', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            // The client clock stalled during a 60s sleep: the server moved on.
            const fetchMock = stubServerTimeResponse(SERVER_TIME + 60)

            showTab()
            await flushPromises()

            expect(fetchMock).toHaveBeenCalledWith(SERVER_TIME_URL, expect.objectContaining({ cache: 'no-store' }))
            expect(controller.timerDisplayTarget.textContent).toBe('00:29:00')
        })

        it('resyncs when the wall clock jumps ahead of the stalled client clock', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            const fetchMock = stubServerTimeResponse(SERVER_TIME + 60)

            // Only the wall clock moves, as performance.now() does during a sleep.
            vi.setSystemTime(Date.now() + 60_000)
            await vi.advanceTimersByTimeAsync(20)
            await flushPromises()

            expect(fetchMock).toHaveBeenCalledTimes(1)
            expect(controller.timerDisplayTarget.textContent).toBe('00:29:00')
        })


        it('does not resync while both clocks run together', async () => {
            await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            const fetchMock = stubServerTimeResponse(SERVER_TIME)

            await vi.advanceTimersByTimeAsync(5_000)

            expect(fetchMock).not.toHaveBeenCalled()
        })

        it('moves the anchor backward only when the client clock is proven ahead', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            // The client clock ran 2s fast; the server replies within the round trip.
            controller.syncedServerTime = SERVER_TIME + 2
            stubServerTimeResponse(SERVER_TIME)

            showTab()
            await flushPromises()

            expect(controller.getCurrentServerTime()).toBeCloseTo(SERVER_TIME * 1000, 3)
            expect(controller.timerDisplayTarget.textContent).toBe('00:30:00')
        })

        it('keeps the anchor when the response carries no server time', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC, serverTimeUrl: SERVER_TIME_URL })
            stubServerTimeResponse(null)

            showTab()
            await flushPromises()

            expect(controller.getCurrentServerTime()).toBeCloseTo(SERVER_TIME * 1000, 3)
        })
    })
})
