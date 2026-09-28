import { vi } from 'vitest'

// Minimal Web Audio stand-in (jsdom has none). Starts 'suspended', like a
// browser blocking sound until the user interacts with the page.
export class FakeAudioContext extends EventTarget {
    static instances: FakeAudioContext[] = []
    static initialState: AudioContextState = 'suspended'

    state: AudioContextState = FakeAudioContext.initialState
    destination = {}
    played: unknown[] = []
    resume = vi.fn(async () => this.setState('running'))
    close = vi.fn(async () => this.setState('closed'))
    decodeAudioData = vi.fn(async (data: ArrayBuffer) => ({ decodedFrom: data }))

    constructor() {
        super()
        FakeAudioContext.instances.push(this)
    }

    createBufferSource() {
        const source = {
            buffer: null as unknown,
            connect: vi.fn(),
            start: vi.fn(() => this.played.push(source.buffer)),
        }
        return source
    }

    setState(state: AudioContextState) {
        this.state = state
        this.dispatchEvent(new Event('statechange'))
    }
}

export function installFakeAudioContext() {
    FakeAudioContext.instances = []
    FakeAudioContext.initialState = 'suspended'
    Object.defineProperty(globalThis, 'AudioContext', { value: FakeAudioContext, configurable: true })
}
