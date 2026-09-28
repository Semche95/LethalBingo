import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { Application } from '@hotwired/stimulus'

const { emit } = vi.hoisted(() => ({ emit: vi.fn() }))
vi.mock('@symfony/ux-live-component', () => ({
    getComponent: vi.fn().mockResolvedValue({ emit }),
}))

import GameFrameController from '../controllers/game/game_frame_controller'

class FakeEventSource {
    static instances: FakeEventSource[] = []
    onopen: (() => void) | null = null
    onmessage: ((event: { data: string }) => void) | null = null
    close = vi.fn()

    constructor(public url: string) {
        FakeEventSource.instances.push(this)
    }
}

let app: Application
let element: HTMLDivElement

async function connectController() {
    element = document.createElement('div')
    element.setAttribute('data-controller', 'game--game-frame')
    element.setAttribute('data-game--game-frame-mercure-url-value', 'https://hub/.well-known/mercure?topic=game')
    document.body.appendChild(element)

    for (let i = 0; i < 50; i++) {
        if (app.getControllerForElementAndIdentifier(element, 'game--game-frame') && FakeEventSource.instances.length) {
            // Let the async initialize() resolve the Live component.
            await Promise.resolve()
            return
        }
        await Promise.resolve()
    }
    throw new Error('Controller never connected')
}

beforeEach(async () => {
    FakeEventSource.instances = []
    vi.stubGlobal('EventSource', FakeEventSource)

    app = new Application(document.documentElement)
    app.register('game--game-frame', GameFrameController)
    await app.start()
})

// Application.stop() does not disconnect the controllers: removing the element
// first lets Stimulus call disconnect() and remove the listeners.
afterEach(async () => {
    document.body.innerHTML = ''
    await Promise.resolve()
    app.stop()
    vi.unstubAllGlobals()
    vi.clearAllMocks()
})

describe('game_frame_controller', () => {
    it('forwards Mercure messages to the Live components', async () => {
        await connectController()

        FakeEventSource.instances[0].onmessage!({ data: JSON.stringify({ event: 'gameTimerRefresh', serverTime: 1_700_000_000.5 }) })

        expect(emit).toHaveBeenCalledWith('gameTimerRefresh', { serverTime: 1_700_000_000.5 })
    })

    // Also covers a finished game, where the timer is no longer on the page.
    it('reopens the connection and reloads the game after a sleep', async () => {
        vi.useFakeTimers({ toFake: ['Date', 'performance', 'requestAnimationFrame', 'cancelAnimationFrame'] })
        try {
            await connectController()
            const first = FakeEventSource.instances[0]

            // Only the wall clock moves, as performance.now() does during a sleep.
            vi.setSystemTime(Date.now() + 60_000)
            await vi.advanceTimersByTimeAsync(20)

            expect(first.close).toHaveBeenCalled()
            expect(FakeEventSource.instances).toHaveLength(2)
            expect(emit).toHaveBeenCalledWith('gameFrameRefresh', {})
        } finally {
            vi.useRealTimers()
        }
    })

    it('reopens the connection and reloads the game when the network comes back', async () => {
        await connectController()

        window.dispatchEvent(new Event('online'))

        expect(FakeEventSource.instances).toHaveLength(2)
        expect(emit).toHaveBeenCalledWith('gameFrameRefresh', {})
    })

    it('reloads the game when the browser reconnects by itself, not on the first connection', async () => {
        await connectController()
        const source = FakeEventSource.instances[0]

        source.onopen!()
        expect(emit).not.toHaveBeenCalled()

        source.onopen!()
        expect(emit).toHaveBeenCalledWith('gameFrameRefresh', {})
    })

    it('stops listening once disconnected', async () => {
        await connectController()
        element.remove()
        await Promise.resolve()

        window.dispatchEvent(new Event('online'))

        expect(FakeEventSource.instances[0].close).toHaveBeenCalled()
        expect(FakeEventSource.instances).toHaveLength(1)
    })
})
