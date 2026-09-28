import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { TimerSound } from '../controllers/game/game_timer_sound'
import { FakeAudioContext, installFakeAudioContext } from './fake_audio_context'

const URLS = { notification: '/sounds/interval.mp3', end: '/sounds/end.mp3' }

async function flushPromises() {
    for (let i = 0; i < 10; i++) {
        await Promise.resolve()
    }
}

describe('TimerSound', () => {
    beforeEach(() => {
        installFakeAudioContext()
        vi.stubGlobal('fetch', vi.fn(async (url: string) => ({ arrayBuffer: async () => url })))
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

    it('keeps working silently when a sound cannot be loaded', async () => {
        vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('offline')))
        const sound = new TimerSound(URLS)
        sound.unlock()
        await flushPromises()

        await expect(sound.play('notification')).resolves.toBeUndefined()
        expect(FakeAudioContext.instances[0].played).toEqual([])
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
