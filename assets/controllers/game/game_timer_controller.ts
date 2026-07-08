import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override values = {
        isHighlighted: Boolean,
        cancelSounds: Boolean,
        timerReachedZero: Boolean,
        isGameFinished: Boolean,
    }

    declare readonly isHighlightedValue: boolean
    declare readonly cancelSoundsValue: boolean
    declare readonly timerReachedZeroValue: boolean
    declare readonly isGameFinishedValue: boolean

    private notificationAudio: HTMLAudioElement | null = null
    private endAudio: HTMLAudioElement | null = null

    override connect() {
        const notificationSoundURL = new URL('/assets/sounds/interval.mp3', import.meta.url)
        const endSoundURL = new URL('/assets/sounds/end.mp3', import.meta.url)
        this.notificationAudio = new Audio(notificationSoundURL.href)
        this.endAudio = new Audio(endSoundURL.href)
    }

    override disconnect() {
        this.resetAudio(true)
        this.notificationAudio = null
        this.endAudio = null
    }

    isHighlightedValueChanged() {
        this.playNotificationSound()
    }

    cancelSoundsValueChanged() {
        if (this.cancelSoundsValue) {
            this.resetAudio()
        }
    }

    timerReachedZeroValueChanged() {
        if (this.timerReachedZeroValue) {
            this.playEndOfTimerSound()
        }
    }

    private resetAudio(cleanup = false) {
        if (this.notificationAudio) {
            this.notificationAudio.pause()
            this.notificationAudio.currentTime = 0
            if (cleanup) this.notificationAudio.src = ''
        }

        if (this.endAudio) {
            this.endAudio.pause()
            this.endAudio.currentTime = 0
            if (cleanup) this.endAudio.src = ''
        }
    }

    private playAudio(audio: HTMLAudioElement | null, errorType: string) {
        // Don't play sounds if the game is finished
        if (this.isGameFinishedValue) {
            return
        }

        try {
            if (audio) {
                audio.pause()
                audio.currentTime = 0
                audio.play().catch(e => console.log(`Error playing ${errorType}: ${e}`))
            }
        } catch (e) {
            console.error(`${errorType} sound failed: ${e}`)
        }
    }

    playNotificationSound() {
        this.playAudio(this.notificationAudio, 'notification')
    }

    playEndOfTimerSound() {
        this.playAudio(this.endAudio, 'end')
    }
}
