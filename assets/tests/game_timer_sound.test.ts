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
})
