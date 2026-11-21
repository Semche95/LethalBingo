import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { TimerSound } from '../controllers/game/game_timer_sound'
import { FakeAudioContext, installFakeAudioContext } from './fake_audio_context'

const URLS = { start: '/sounds/start.mp3', notification: '/sounds/interval.mp3', end: '/sounds/end.mp3' }

async function flushPromises() {
    for (let i = 0; i < 10; i++) {
        await Promise.resolve()
    }
}

describe('TimerSound', () => {
    beforeEach(() => {
        installFakeAudioContext()
        vi.stubGlobal('fetch', vi.fn(async (url: string) => ({ ok: true, arrayBuffer: async () => url })))
    })

    afterEach(() => {
        vi.unstubAllGlobals()
    })

    it('stays silent while the browser blocks sound', async () => {
        const sound = new TimerSound(URLS)

        await sound.play('notification')

        expect(sound.isLocked).toBe(true)
        expect(FakeAudioContext.instances[0].played).toEqual([])
    })

    it('plays the requested sound once unlocked', async () => {
        const sound = new TimerSound(URLS)
        const onLockChange = vi.fn()
        sound.onLockChange(onLockChange)

        sound.unlock()
        await flushPromises()
        await sound.play('end')

        expect(sound.isLocked).toBe(false)
        expect(onLockChange).toHaveBeenCalled()
        expect(FakeAudioContext.instances[0].played).toEqual([{ decodedFrom: URLS.end }])
    })

    describe('missing sound', () => {
        let consoleError: ReturnType<typeof vi.spyOn>

        beforeEach(() => {
            consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
        })

        afterEach(() => {
            consoleError.mockRestore()
        })

        function stubFetchFailingOn(failingUrl: string, failure: () => Promise<unknown>) {
            vi.stubGlobal('fetch', vi.fn(async (url: string) => url === failingUrl
                ? failure()
                : { ok: true, arrayBuffer: async () => url }))
        }

        it.each([
            ['unreachable', async () => { throw new Error('offline') }],
            ['not found', async () => ({ ok: false, status: 404, arrayBuffer: async () => 'Not Found' })],
        ])('reports a sound that is %s in the console, once', async (_, failure) => {
            stubFetchFailingOn(URLS.notification, failure)

            new TimerSound(URLS)
            await flushPromises()

            expect(consoleError).toHaveBeenCalledTimes(1)
            expect(consoleError).toHaveBeenCalledWith(expect.stringContaining(URLS.notification), expect.any(Error))
        })

        it('does not decode a sound the server did not find', async () => {
            stubFetchFailingOn(URLS.notification, async () => ({ ok: false, status: 404, arrayBuffer: async () => 'Not Found' }))

            new TimerSound(URLS)
            await flushPromises()

            expect(FakeAudioContext.instances[0].decodeAudioData).not.toHaveBeenCalledWith('Not Found')
        })

        it('reports an unreadable sound in the console', async () => {
            new TimerSound(URLS)
            // Decoding only starts once the download resolves, after this line.
            FakeAudioContext.instances[0].decodeAudioData.mockImplementation(async (data: unknown) => {
                if (data === URLS.end) {
                    throw new Error('EncodingError')
                }
                return { decodedFrom: data } as never
            })
            await flushPromises()

            expect(consoleError).toHaveBeenCalledTimes(1)
            expect(consoleError).toHaveBeenCalledWith(expect.stringContaining(URLS.end), expect.any(Error))
        })

        it('keeps playing and scheduling the other sounds', async () => {
            stubFetchFailingOn(URLS.notification, async () => { throw new Error('offline') })
            const sound = new TimerSound(URLS)
            const context = FakeAudioContext.instances[0]
            sound.unlock()
            await flushPromises()

            await expect(sound.play('notification')).resolves.toBeUndefined()
            await sound.play('end')
            await sound.schedule([{ name: 'notification', inSeconds: 1 }, { name: 'start', inSeconds: 2 }])

            expect(context.played).toEqual([{ decodedFrom: URLS.end }, { decodedFrom: URLS.start }])
        })

        it('reports nothing when every sound loads', async () => {
            new TimerSound(URLS)
            await flushPromises()

            expect(consoleError).not.toHaveBeenCalled()
        })
    })

    it('stops notifying lock changes once unsubscribed', async () => {
        const sound = new TimerSound(URLS)
        const onLockChange = vi.fn()
        sound.onLockChange(onLockChange)()

        sound.unlock()
        await flushPromises()

        expect(onLockChange).not.toHaveBeenCalled()
    })

    it('schedules each sound on the audio clock, replacing the previous schedule', async () => {
        const sound = new TimerSound(URLS)
        const context = FakeAudioContext.instances[0]
        sound.unlock()
        await flushPromises()
        context.currentTime = 100

        await sound.schedule([{ name: 'notification', inSeconds: 1.5 }])
        await sound.schedule([{ name: 'notification', inSeconds: 2 }, { name: 'end', inSeconds: 60 }])

        expect(context.starts).toEqual([
            { buffer: { decodedFrom: URLS.notification }, when: 101.5, stopped: true },
            { buffer: { decodedFrom: URLS.notification }, when: 102, stopped: false },
            { buffer: { decodedFrom: URLS.end }, when: 160, stopped: false },
        ])
    })

    it('stops the scheduled sounds, including a schedule still loading its sounds', async () => {
        const sound = new TimerSound(URLS)
        const context = FakeAudioContext.instances[0]
        sound.unlock()
        await flushPromises()
        await sound.schedule([{ name: 'end', inSeconds: 10 }])

        const loading = sound.schedule([{ name: 'end', inSeconds: 20 }])
        sound.cancelScheduled()
        await loading

        expect(context.starts).toEqual([{ buffer: { decodedFrom: URLS.end }, when: 10, stopped: true }])
    })

    it('schedules nothing while the browser blocks sound', async () => {
        const sound = new TimerSound(URLS)

        await sound.schedule([{ name: 'end', inSeconds: 10 }])

        expect(FakeAudioContext.instances[0].starts).toEqual([])
    })
})
