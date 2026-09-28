import { vi } from 'vitest'

// Minimal Web Audio stand-in (jsdom has none). Starts 'suspended', like a
// browser blocking sound until the user interacts with the page.
export class FakeAudioContext extends EventTarget {
    static instances: FakeAudioContext[] = []
    static initialState: AudioContextState = 'suspended'

    state: AudioContextState = FakeAudioContext.initialState
    destination = {}
    currentTime = 0
    played: unknown[] = []
    // Every started sound with its start time on the audio clock.
    starts: Array<{ buffer: unknown, when: number, stopped: boolean }> = []
    resume = vi.fn(async () => this.setState('running'))
    close = vi.fn(async () => this.setState('closed'))
    decodeAudioData = vi.fn(async (data: ArrayBuffer) => ({ decodedFrom: data }))

    constructor() {
        super()
        FakeAudioContext.instances.push(this)
    }

    createBufferSource() {
        let started: { buffer: unknown, when: number, stopped: boolean } | null = null
        const source = {
            buffer: null as unknown,
            onended: null as (() => void) | null,
            connect: vi.fn(),
            start: vi.fn((when: number = 0) => {
                this.played.push(source.buffer)
                started = { buffer: source.buffer, when, stopped: false }
                this.starts.push(started)
            }),
            stop: vi.fn(() => {
                if (started) {
                    started.stopped = true
                }
            }),
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
