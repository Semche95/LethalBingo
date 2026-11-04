import { Controller } from '@hotwired/stimulus'
import { Tooltip } from 'bootstrap'
import { postJson } from '../../csrf_post'
import { debounce, disposeTooltips, initTooltips, showToast } from '../../utils'

// The menu is opened and closed here rather than by a Bootstrap dropdown, whose
// toggle cannot sit in the wrapper that shows the tooltip of the disabled button.
// A click outside closes the menu without being stopped, so it still reaches its target.
export default class extends Controller<HTMLDivElement> {
    static override targets = ['token', 'listTooltip', 'listButton', 'badge', 'menu', 'row']
    static override values = {
        addUrl: String,
    }

    declare readonly tokenTarget: HTMLInputElement
    declare readonly listTooltipTarget: HTMLSpanElement
    declare readonly listButtonTarget: HTMLButtonElement
    declare readonly badgeTarget: HTMLSpanElement
    declare readonly menuTarget: HTMLUListElement
    declare readonly rowTargets: HTMLLIElement[]
    declare readonly addUrlValue: string

    private marking = false
    private readonly noteSaves = new Map<HTMLLIElement, ReturnType<typeof debounce>>()

    override connect(): void {
        initTooltips(this.element)
        document.addEventListener('click', this.onDocumentClick)
        document.addEventListener('keydown', this.onDocumentKeydown)
    }

    override disconnect(): void {
        document.removeEventListener('click', this.onDocumentClick)
        document.removeEventListener('keydown', this.onDocumentKeydown)
        this.noteSaves.forEach(save => save.cancel())
        this.noteSaves.clear()
        disposeTooltips(this.element)
    }

    async mark(): Promise<void> {
        if (this.marking) {
            return
        }
        this.marking = true

        try {
            const response = await this.post(this.addUrlValue)
            this.menuTarget.insertAdjacentHTML('afterbegin', await response.text())
        } catch {
            showToast('Le temps fort n\'a pas pu être marqué', 'danger')
            return
        } finally {
            this.marking = false
        }

        this.updateCount()
        this.open()
        this.menuTarget.scrollTop = 0
        this.menuTarget.querySelector<HTMLInputElement>('input')?.focus()
    }

    toggle(): void {
        if (this.isOpen()) {
            this.close()
        } else {
            this.open()
        }
    }

    saveNote(event: Event): void {
        const input = event.currentTarget as HTMLInputElement
        const row = this.rowOf(input)
        let save = this.noteSaves.get(row)
        if (!save) {
            save = debounce(() => this.doSaveNote(row, input), 100)
            this.noteSaves.set(row, save)
        }
        save()
    }

    private async doSaveNote(row: HTMLLIElement, input: HTMLInputElement): Promise<void> {
        try {
            await this.post(row.dataset.noteUrl ?? '', { note: input.value })
            input.classList.remove('is-invalid')
        } catch {
            input.classList.add('is-invalid')
        }
    }

    askDelete(event: Event): void {
        const row = this.rowOf(event.currentTarget as HTMLElement)
        this.rowTargets.forEach(other => this.showConfirm(other, other === row))
        this.confirmLine(row)?.querySelector('button')?.focus()
    }

    cancelDelete(event: Event): void {
        this.restoreRow(this.rowOf(event.currentTarget as HTMLElement))
    }

    async delete(event: Event): Promise<void> {
        const row = this.rowOf(event.currentTarget as HTMLElement)
        this.noteSaves.get(row)?.cancel()

        try {
            await this.post(row.dataset.deleteUrl ?? '')
        } catch {
            showToast('Le temps fort n\'a pas pu être supprimé', 'danger')
            return
        }

        this.noteSaves.delete(row)
        row.remove()
        this.updateCount()
        if (this.rowTargets.length === 0) {
            this.close()
        }
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

        const confirming = this.rowTargets.find(row => this.confirmLine(row)?.classList.contains('d-none') === false)
        if (confirming) {
            this.restoreRow(confirming)

            return
        }

        this.close()
        this.listButtonTarget.focus()
    }

    private isOpen(): boolean {
        return this.menuTarget.classList.contains('show')
    }

    private open(): void {
        this.menuTarget.classList.add('show')
        this.listButtonTarget.setAttribute('aria-expanded', 'true')
    }

    private close(): void {
        this.menuTarget.classList.remove('show')
        this.listButtonTarget.setAttribute('aria-expanded', 'false')
        this.rowTargets.forEach(row => this.showConfirm(row, false))
    }

    private updateCount(): void {
        const count = this.rowTargets.length
        this.badgeTarget.textContent = String(count)
        this.badgeTarget.classList.toggle('d-none', count === 0)
        this.listButtonTarget.disabled = count === 0
        // Kept in the attribute too: a tooltip recreated after a re-render reads it.
        const title = count === 0 ? 'Aucun temps fort' : 'Temps forts'
        this.listTooltipTarget.setAttribute('data-bs-original-title', title)
        Tooltip.getInstance(this.listTooltipTarget)?.setContent({ '.tooltip-inner': title })
    }

    private restoreRow(row: HTMLLIElement): void {
        this.showConfirm(row, false)
        row.querySelector<HTMLButtonElement>('.game-highlight-delete')?.focus()
    }

    private showConfirm(row: HTMLLIElement, confirm: boolean): void {
        row.querySelector('[data-game-highlight-view]')?.classList.toggle('d-none', confirm)
        this.confirmLine(row)?.classList.toggle('d-none', !confirm)
    }

    // Null once the game is over: the list is read-only.
    private confirmLine(row: HTMLLIElement): HTMLElement | null {
        return row.querySelector<HTMLElement>('[data-game-highlight-confirm]')
    }

    private rowOf(element: HTMLElement): HTMLLIElement {
        return element.closest<HTMLLIElement>('.game-highlight') as HTMLLIElement
    }

    private post(url: string, body?: object): Promise<Response> {
        return postJson(url, this.tokenTarget.value, body)
    }
}
