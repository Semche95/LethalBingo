import { Controller } from '@hotwired/stimulus'

export default class extends Controller<HTMLDivElement> {
    static override targets = ['notes', 'status', 'panel', 'toggle']
    static override values = {
        gameId: Number,
        saveUrl: String,
    }

    declare readonly notesTarget: HTMLTextAreaElement
    declare readonly statusTarget: HTMLDivElement
    declare readonly panelTarget: HTMLDivElement
    declare readonly toggleTarget: HTMLButtonElement
    declare readonly gameIdValue: number
    declare readonly saveUrlValue: string

    private saveTimeout: number | null = null
    private heightSaveTimeout: number | null = null
    private resizeObserver: ResizeObserver | null = null
    private readonly debounceTime = 100 // 100ms debounce for notes
    private readonly heightDebounceTime = 50 // 50ms debounce for height

    saveNotes(): void {
        // Clear any existing timeout
        if (this.saveTimeout !== null) {
            window.clearTimeout(this.saveTimeout)
        }

        // Set a new timeout to save after debounce time
        this.saveTimeout = window.setTimeout(() => {
            this.doSaveNotes()
        }, this.debounceTime)

        // Update status
        this.statusTarget.textContent = 'Sauvegarde en cours...'
    }

    private async doSaveNotes(): Promise<void> {
        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
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
        // Clear any existing timeout
        if (this.heightSaveTimeout !== null) {
            window.clearTimeout(this.heightSaveTimeout)
        }

        // Set a new timeout to save after height debounce time
        this.heightSaveTimeout = window.setTimeout(() => {
            this.doSaveTextareaHeight()
        }, this.heightDebounceTime)
    }

    private doSaveTextareaHeight(): void {
        // Get the computed height to ensure we get the actual rendered height
        const computedHeight = window.getComputedStyle(this.notesTarget).height
        const storageKey = `game-notes-height-${this.gameIdValue}`
        localStorage.setItem(storageKey, computedHeight)
    }

    override connect(): void {
        // Check localStorage for saved panel state
        const panelStorageKey = `game-notes-panel-${this.gameIdValue}`
        const isPanelVisible = localStorage.getItem(panelStorageKey) === 'visible'

        if (isPanelVisible) {
            this.showPanel()
        } else {
            this.hidePanel()
        }

        // Restore textarea height if saved
        const heightStorageKey = `game-notes-height-${this.gameIdValue}`
        const savedHeight = localStorage.getItem(heightStorageKey)
        if (savedHeight) {
            this.notesTarget.style.height = savedHeight
        }

        // Initialize ResizeObserver to detect height changes
        this.resizeObserver = new ResizeObserver(() => {
            this.saveTextareaHeight()
        })
        this.resizeObserver.observe(this.notesTarget)
    }

    override disconnect(): void {
        // Clean up ResizeObserver
        if (this.resizeObserver) {
            this.resizeObserver.disconnect()
            this.resizeObserver = null
        }
    }

    togglePanel(event: Event): void {
        if (event.currentTarget === this.toggleTarget) {
            if (this.panelTarget.classList.contains('d-none')) {
                this.showPanel()
            } else {
                this.hidePanel()
            }
        }
    }

    showPanel(): void {
        this.panelTarget.classList.remove('d-none')
        this.toggleTarget.setAttribute('aria-expanded', 'true')
        this.toggleTarget.setAttribute('title', 'Masquer les notes')

        // Save panel state to localStorage
        const storageKey = `game-notes-panel-${this.gameIdValue}`
        localStorage.setItem(storageKey, 'visible')
    }

    hidePanel(): void {
        this.panelTarget.classList.add('d-none')
        this.toggleTarget.setAttribute('aria-expanded', 'false')
        this.toggleTarget.setAttribute('title', 'Afficher les notes')

        // Save panel state to localStorage
        const storageKey = `game-notes-panel-${this.gameIdValue}`
        localStorage.setItem(storageKey, 'hidden')
    }
}
