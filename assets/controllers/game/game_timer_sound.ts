export type TimerSoundName = 'notification' | 'end'

/**
 * Timer sounds played through the Web Audio API. Browsers block sound until
 * the user has interacted with the page (click, key press): unlike an <audio>
 * element, the audio context tells whether sound is still blocked, so the page
 * can ask the user to enable it before an alert is lost.
 */
export class TimerSound {
    private readonly context = new AudioContext()
    private readonly buffers: Map<TimerSoundName, Promise<AudioBuffer | null>>

    constructor(urls: Record<TimerSoundName, string>) {
        this.buffers = new Map(
            (Object.entries(urls) as [TimerSoundName, string][]).map(([name, url]) => [name, this.load(url)]),
        )
    }

    get isLocked(): boolean {
        return this.context.state !== 'running'
    }

    // Only takes effect from a user gesture handler.
    unlock(): void {
        if (this.context.state === 'suspended') {
            this.context.resume().catch(() => {})
        }
    }

    // Returns the function that stops listening.
    onLockChange(listener: () => void): () => void {
        this.context.addEventListener('statechange', listener)
        return () => this.context.removeEventListener('statechange', listener)
    }

    async play(name: TimerSoundName): Promise<void> {
        const buffer = await this.buffers.get(name)
        if (!buffer || this.isLocked) {
            return
        }

        const source = this.context.createBufferSource()
        source.buffer = buffer
        source.connect(this.context.destination)
        source.start()
    }

    close(): void {
        this.context.close().catch(() => {})
    }

    private async load(url: string): Promise<AudioBuffer | null> {
        try {
            const response = await fetch(url)
            return await this.context.decodeAudioData(await response.arrayBuffer())
        } catch {
            // Missing sound: the timer keeps working silently.
            return null
        }
    }
}
