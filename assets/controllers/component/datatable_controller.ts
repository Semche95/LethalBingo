import { Controller } from '@hotwired/stimulus'
import { visit } from '@hotwired/turbo'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    // Define values that can be passed from the HTML
    static override values = {
        id: String,
    }

    declare readonly idValue: string

    // Track if we've already processed the current table
    private processedId: string = ''

    // Store references to event handlers for cleanup
    private rowHandlers: Map<HTMLElement, EventListener> = new Map()

    override connect() {
        // Apply click handlers when controller connects
        this.applyClickHandlers()
    }

    override disconnect() {
        // Clean up all event listeners when controller disconnects
        this.removeAllClickHandlers()
    }

    /**
     * This method is automatically called by Stimulus whenever the idValue changes
     * This happens when the LiveComponent updates the table with new data
     */
    idValueChanged() {
        // Only process if the ID has actually changed
        if (this.idValue !== this.processedId) {
            this.processedId = this.idValue
            // Remove old handlers before applying new ones
            this.removeAllClickHandlers()
            this.applyClickHandlers()
        }
    }

    /**
     * Remove all click event listeners
     */
    private removeAllClickHandlers(): void {
        // Remove all stored event listeners
        this.rowHandlers.forEach((handler, row) => {
            row.removeEventListener('click', handler)
        })

        // Clear the map
        this.rowHandlers.clear()
    }

    private applyClickHandlers(): void {
        // Find all rows in the current table
        const rows = this.element.querySelectorAll('tbody tr')

        rows.forEach((row) => {
            // Skip if the row already has a click handler
            if (row.classList.contains('cursor-pointer')) {
                return
            }

            const link = row.querySelector('a[href]')
            if (!link) {
                return
            }

            // Create a handler function that we can reference later for removal
            const clickHandler = (e: Event) => {
                if (!(e.target instanceof HTMLElement) || e.target.tagName === 'A' || e.target.parentElement?.tagName === 'A') {
                    return
                }

                const url = (link as HTMLAnchorElement).getAttribute('href') || ''
                visit(url)
            }

            // Store the handler reference for later cleanup
            this.rowHandlers.set(row as HTMLElement, clickHandler)

            row.classList.add('cursor-pointer')
            row.addEventListener('click', clickHandler)
        })
    }
}
