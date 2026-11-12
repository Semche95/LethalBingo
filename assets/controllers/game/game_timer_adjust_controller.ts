import { Controller } from '@hotwired/stimulus'
import { hideTooltip } from '../../utils'

// Panel where the referee enters the duration to add to or remove from the timer.
// Opened and closed here: Bootstrap's dropdown script is not loaded. The class
// toggled here survives the renders of the timer component, so the panel stays
// open while the time is adjusted, until a click outside or Escape.
export default class extends Controller<HTMLDivElement> {
    static override targets = ['button', 'menu', 'input']

    declare readonly buttonTarget: HTMLButtonElement
    declare readonly menuTarget: HTMLDivElement
    declare readonly inputTarget: HTMLInputElement

    override connect(): void {
        document.addEventListener('click', this.onDocumentClick)
        document.addEventListener('keydown', this.onDocumentKeydown)
    }

    override disconnect(): void {
        document.removeEventListener('click', this.onDocumentClick)
        document.removeEventListener('keydown', this.onDocumentKeydown)
    }

    toggle(): void {
        // Shown on focus too, the tooltip would stay over the open panel
        hideTooltip(this.buttonTarget)
        if (this.isOpen()) {
            this.close()
            return
        }

        this.menuTarget.classList.add('show')
        this.buttonTarget.setAttribute('aria-expanded', 'true')
        this.inputTarget.select()
    }

    // Minutes alone ("2" or "2:") get their seconds when leaving the field.
    // The input event updates the duration of the timer component.
    completeSeconds(): void {
        const minutes = /^\s*(\d{1,3}):?\s*$/.exec(this.inputTarget.value)?.[1]
        if (minutes === undefined) {
            return
        }

        this.inputTarget.value = `${minutes}:00`
        this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }))
    }

    private readonly onDocumentClick = (event: MouseEvent): void => {
        if (this.isOpen() && !event.composedPath().includes(this.element)) {
            this.close()
        }
    }

    private readonly onDocumentKeydown = (event: KeyboardEvent): void => {
        if (event.key !== 'Escape' || !this.isOpen()) {
            return
        }
        event.preventDefault()
        this.close()
        this.buttonTarget.focus()
    }

    private isOpen(): boolean {
        return this.menuTarget.classList.contains('show')
    }

    private close(): void {
        // The error of a rejected entry goes with the panel, as on the server.
        this.inputTarget.classList.remove('is-invalid')
        this.menuTarget.querySelector('.invalid-feedback')?.remove()
        this.menuTarget.classList.remove('show')
        this.buttonTarget.setAttribute('aria-expanded', 'false')
    }
}
