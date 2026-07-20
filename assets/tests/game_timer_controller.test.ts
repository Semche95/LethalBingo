import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { Application } from '@hotwired/stimulus'
import GameTimerController from '../controllers/game/game_timer_controller'

// ---------------------------------------------------------------------------
// Test environment setup
// ---------------------------------------------------------------------------

// jsdom does not implement Audio the way the controller expects; provide a stub
// that records play() calls so we can assert on sound effects.
class FakeAudio {
    src = ''
    currentTime = 0
    play = vi.fn().mockResolvedValue(undefined)
    pause = vi.fn()
}
const FakeAudioCtor = vi.fn().mockImplementation(() => new FakeAudio()) as unknown as typeof Audio

let app: Application

function kebab(name: string): string {
    return name.replace(/[A-Z]/g, (m) => '-' + m.toLowerCase())
}

interface TimerValues {
    gameStartedAt?: number | null
    gameFinishedAt?: number | null
    gameTotalPausedSeconds?: number
    gameLastPausedAt?: number | null
    gameIsPaused?: boolean
    serverTime?: number
    serverTimeUrl?: string
}

function buildElement(values: TimerValues = {}) {
    const el = document.createElement('div')
    el.setAttribute('data-controller', 'game--game-timer')

    for (const [key, val] of Object.entries(values)) {
        if (val === null || val === undefined) continue
        el.setAttribute(`data-game--game-timer-${kebab(key)}-value`, String(val))
    }

    el.innerHTML = `
        <span data-game--game-timer-target="timerDisplay"></span>
        <span data-game--game-timer-target="pauseBadge" class="d-none"></span>
        <button data-game--game-timer-target="pauseButton"></button>
    `
    document.body.appendChild(el)
    return el
}

// Stimulus connects controllers asynchronously (MutationObserver). Wait until
// the controller is available.
async function connectController(el: HTMLElement): Promise<any> {
    for (let i = 0; i < 50; i++) {
        const controller = app.getControllerForElementAndIdentifier(el, 'game--game-timer')
        if (controller) return controller
        await Promise.resolve()
    }
    throw new Error('Controller never connected')
}

// Redefine a Stimulus value getter on the controller instance, mimicking Twig
// writing a new value into the DOM (and the value-changed callback firing).
function overrideValue(controller: any, name: string, value: unknown) {
    Object.defineProperty(controller, name, { configurable: true, get: () => value })
}

// JSON responses helper for fetch mocking.
function jsonResponse(serverTime: unknown, ok = true) {
    return {
        ok,
        json: () => Promise.resolve({ serverTime }),
    }
}

beforeEach(async () => {
    // Fake timers including performance.now() so that the client-side time
    // estimate advances deterministically alongside setInterval.
    vi.useFakeTimers({
        toFake: [
            'setTimeout',
            'setInterval',
            'clearTimeout',
            'clearInterval',
            'Date',
            'performance',
            'requestAnimationFrame',
            'cancelAnimationFrame',
        ],
    })

    Object.defineProperty(globalThis, 'Audio', { value: FakeAudioCtor, configurable: true })
    globalThis.fetch = vi.fn()

    app = new Application(document.documentElement)
    app.register('game--game-timer', GameTimerController)
    await app.start()
})

afterEach(() => {
    app.stop()
    document.body.innerHTML = ''
    vi.runOnlyPendingTimers()
    vi.useRealTimers()
    vi.clearAllMocks()
})

// ---------------------------------------------------------------------------
// Sync pause/stop behaviour
// ---------------------------------------------------------------------------

describe('game_timer_controller sync interval pause/stop behaviour', () => {
    const serverTime = 1_700_000_000
    const startSec = serverTime - 1800

    it('starts with sync active when the timer is running', async () => {
        const el = buildElement({
            gameStartedAt: startSec,
            gameTotalPausedSeconds: 0,
            gameIsPaused: false,
            serverTime,
            serverTimeUrl: '/game/server-time',
        })
        const controller = await connectController(el)

        expect(controller.syncInterval).not.toBeNull()
    })

    it('stops the sync interval when paused', async () => {
        const el = buildElement({
            gameStartedAt: startSec,
            gameTotalPausedSeconds: 0,
            gameIsPaused: false,
            serverTime,
            serverTimeUrl: '/game/server-time',
        })
        const controller = await connectController(el)
        expect(controller.syncInterval).not.toBeNull()

        // Reset the fetch mock so we only observe calls triggered by the pause.
        vi.clearAllMocks()

        overrideValue(controller, 'gameIsPausedValue', true)
        controller.gameIsPausedValueChanged()

        expect(controller.syncInterval).toBeNull()
        expect(globalThis.fetch).not.toHaveBeenCalled()
    })

    it('restarts the sync interval and re-syncs immediately on resume', async () => {
        const el = buildElement({
            gameStartedAt: startSec,
            gameTotalPausedSeconds: 0,
            gameIsPaused: true,
            serverTime,
            serverTimeUrl: '/game/server-time',
        })
        const controller = await connectController(el)
        expect(controller.syncInterval).toBeNull()

        ;(globalThis.fetch as any).mockResolvedValue(jsonResponse(serverTime + 7))
        overrideValue(controller, 'gameIsPausedValue', false)
        controller.gameIsPausedValueChanged()

        expect(controller.syncInterval).not.toBeNull()

        // The immediate re-sync is fire-and-forget; flush its fetch + resolution.
        await vi.advanceTimersByTimeAsync(0)

        expect(globalThis.fetch).toHaveBeenCalledWith('/game/server-time')
        expect(controller.syncedServerTime).toBe(serverTime + 7)
    })

    it('stops the sync interval when the game finishes', async () => {
        const el = buildElement({
            gameStartedAt: startSec,
            gameTotalPausedSeconds: 0,
            gameIsPaused: false,
            serverTime,
            serverTimeUrl: '/game/server-time',
        })
        const controller = await connectController(el)
        expect(controller.syncInterval).not.toBeNull()

        overrideValue(controller, 'gameFinishedAtValue', serverTime)
        controller.gameFinishedAtValueChanged()

        expect(controller.syncInterval).toBeNull()
    })

    it('never starts the sync interval when already paused at connect', async () => {
        const el = buildElement({
            gameStartedAt: startSec,
            gameTotalPausedSeconds: 0,
            gameIsPaused: true,
            serverTime,
            serverTimeUrl: '/game/server-time',
        })
        const controller = await connectController(el)

        expect(controller.syncInterval).toBeNull()

        // syncServerTime() must not poll the server while paused.
        await controller.syncServerTime()
        expect(globalThis.fetch).not.toHaveBeenCalled()
    })

    it('does not start the sync interval when the timer has not started', async () => {
        const el = buildElement({
            serverTime,
            serverTimeUrl: '/game/server-time',
        })
        const controller = await connectController(el)

        expect(controller.syncInterval).toBeNull()
    })
})
