import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { FakeAudioContext } from './fake_audio_context'
import { SERVER_TIME, START_SEC, spyOnSounds, connectWithAnchor, useTimerControllerApp } from './game_timer_controller_setup'

describe('game_timer_controller sounds', () => {
    useTimerControllerApp()

    describe('sound unlock', () => {
        async function flushPromises() {
            for (let i = 0; i < 10; i++) {
                await Promise.resolve()
            }
        }

        it('shows the enable sound button while the browser blocks sound, until the user clicks the page', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC })
            expect(controller.soundButtonTarget.classList.contains('d-none')).toBe(false)

            document.body.click()
            await flushPromises()

            expect(FakeAudioContext.instances[0].resume).toHaveBeenCalled()
            expect(controller.soundButtonTarget.classList.contains('d-none')).toBe(true)
        })

        it('hides the button when sound is already allowed', async () => {
            FakeAudioContext.initialState = 'running'

            const controller = await connectWithAnchor({ gameStartedAt: START_SEC })

            expect(controller.soundButtonTarget.classList.contains('d-none')).toBe(true)
        })

        it('releases the audio context and the page listeners on disconnect', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: START_SEC })
            const context = FakeAudioContext.instances[0]

            controller.element.remove()
            await flushPromises()
            document.body.click()

            expect(context.close).toHaveBeenCalled()
            expect(context.resume).not.toHaveBeenCalled()
        })
    })

    describe('sounds while the page is hidden', () => {
        async function flushPromises() {
            for (let i = 0; i < 10; i++) {
                await Promise.resolve()
            }
        }

        function setVisibility(state: DocumentVisibilityState) {
            Object.defineProperty(document, 'visibilityState', { value: state, configurable: true })
            document.dispatchEvent(new Event('visibilitychange'))
        }

        // Pending sounds on the audio clock, as [name, start time].
        function pendingSounds(): Array<[string, number]> {
            return FakeAudioContext.instances[0].starts
                .filter(({ stopped }) => !stopped)
                .map(({ buffer, when }) => [String((buffer as any).decodedFrom).includes('end') ? 'end' : 'notification', when])
        }

        beforeEach(() => {
            FakeAudioContext.initialState = 'running'
            vi.stubGlobal('fetch', vi.fn(async (url: string) => ({ arrayBuffer: async () => url })))
        })

        afterEach(() => {
            vi.unstubAllGlobals()
        })

        it('schedules the upcoming alerts on the audio clock once the page is hidden', async () => {
            // 00:05:01 remaining: the display drops below 05:00 in 2 s.
            await connectWithAnchor({ gameStartedAt: SERVER_TIME - 3299 })
            expect(pendingSounds()).toEqual([])

            setVisibility('hidden')
            await flushPromises()

            expect(pendingSounds()).toEqual([
                ['notification', 2],
                ['notification', 122],
                ['notification', 242],
                ['end', 301],
            ])
        })

        it('does not replay on return a threshold that sounded while hidden', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: SERVER_TIME - 3299 })
            const plays = spyOnSounds(controller)
            setVisibility('hidden')
            await flushPromises()

            // The display loop is stopped while hidden: time passes without frames.
            controller.clientTimeAtRender -= 3_000
            setVisibility('visible')
            await vi.advanceTimersByTimeAsync(100)

            expect(controller.timerDisplayTarget.textContent).toBe('00:04:58')
            expect(plays('notification')).toBe(0)
            expect(pendingSounds()).toEqual([])
        })

        it('cancels the scheduled alerts when the referee pauses, and reschedules on resume', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: SERVER_TIME - 3299 })
            setVisibility('hidden')
            await flushPromises()

            controller.gamePausedRemainingSecondsValue = 301
            controller.gameCountdownZeroAtValue = 0
            controller.gameIsPausedValue = true
            await flushPromises()
            expect(pendingSounds()).toEqual([])

            // Resumed 10 seconds later: the zero moves back by as much.
            controller.gameIsPausedValue = false
            controller.gameCountdownZeroAtValue = SERVER_TIME + 311
            await flushPromises()
            expect(pendingSounds()[0]).toEqual(['notification', 12])
        })

        it('reschedules after a +1 minute from the referee', async () => {
            const controller = await connectWithAnchor({ gameStartedAt: SERVER_TIME - 3299 })
            setVisibility('hidden')
            await flushPromises()

            controller.gameCountdownZeroAtValue += 60
            await flushPromises()

            expect(pendingSounds()[0]).toEqual(['notification', 62])
        })
    })
})
