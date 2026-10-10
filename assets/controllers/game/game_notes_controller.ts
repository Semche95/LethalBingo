import { Controller } from '@hotwired/stimulus'
import { debounce } from '../../utils'

export default class extends Controller<HTMLDivElement> {
    static override targets = ['notes', 'status', 'panel', 'toggle', 'token']
    static override values = {
        gameId: Number,
        saveUrl: String,
    }

    declare readonly notesTarget: HTMLTextAreaElement
    declare readonly statusTarget: HTMLDivElement
    declare readonly panelTarget: HTMLDivElement
    declare readonly toggleTarget: HTMLButtonElement
    declare readonly tokenTarget: HTMLInputElement
    declare readonly gameIdValue: number
    declare readonly saveUrlValue: string

    private resizeObserver: ResizeObserver | null = null
    private readonly debouncedSaveNotes = debounce(() => this.doSaveNotes(), 100)
    private readonly debouncedSaveTextareaHeight = debounce(() => this.doSaveTextareaHeight(), 50)

    private get panelStorageKey(): string {
        return `game-notes-panel-${this.gameIdValue}`
    }

    private get heightStorageKey(): string {
        return `game-notes-height-${this.gameIdValue}`
    }

    saveNotes(): void {
        this.debouncedSaveNotes()
        this.statusTarget.textContent = 'Sauvegarde en cours...'
    }

    private async doSaveNotes(): Promise<void> {
        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': this.tokenTarget.value,
                },
                body: JSON.stringify({
                    notes: this.notesTarget.value,
                }),
            })

            if (!response.ok) {
                throw new Error('Network response was not ok')
            }

            this.statusTarget.textContent = 'Notes sauvegardées'

            // Reset status message after 3 seconds
            setTimeout(() => {
                this.statusTarget.textContent = 'Notes sauvegardées automatiquement'
            }, 3000)
        } catch (error) {
            this.statusTarget.textContent = 'Erreur lors de la sauvegarde'
        }
    }

    saveTextareaHeight(): void {
        this.debouncedSaveTextareaHeight()
    }

    private doSaveTextareaHeight(): void {
        // Get the computed height to ensure we get the actual rendered height
        localStorage.setItem(this.heightStorageKey, window.getComputedStyle(this.notesTarget).height)
    }

    override connect(): void {
        this.setPanelVisible(localStorage.getItem(this.panelStorageKey) === 'visible')

        // Restore textarea height if saved
        const savedHeight = localStorage.getItem(this.heightStorageKey)
        if (savedHeight) {
            this.notesTarget.style.height = savedHeight
        }

        // Initialize ResizeObserver to detect height changes
        this.resizeObserver = new ResizeObserver(this.debouncedSaveTextareaHeight)
        this.resizeObserver.observe(this.notesTarget)
    }

    override disconnect(): void {
        this.debouncedSaveNotes.cancel()
        this.debouncedSaveTextareaHeight.cancel()

        // Clean up ResizeObserver
        if (this.resizeObserver) {
            this.resizeObserver.disconnect()
            this.resizeObserver = null
        }
    }

    togglePanel(event: Event): void {
        if (event.currentTarget === this.toggleTarget) {
            this.setPanelVisible(this.panelTarget.classList.contains('d-none'))
        }
    }

    private setPanelVisible(visible: boolean): void {
        this.panelTarget.classList.toggle('d-none', !visible)
        this.toggleTarget.setAttribute('aria-expanded', String(visible))
        this.toggleTarget.setAttribute('title', visible ? 'Masquer les notes' : 'Afficher les notes')
        localStorage.setItem(this.panelStorageKey, visible ? 'visible' : 'hidden')
    }
}
