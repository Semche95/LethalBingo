import { Controller } from '@hotwired/stimulus'
import { Component, getComponent } from '@symfony/ux-live-component'

/*
 * History of the game, referee only: drawer opened on demand on narrow
 * screens, and kept scrolled to the newest entry, which flashes when it
 * arrives live.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['panel', 'list']

    declare readonly panelTarget: HTMLElement
    declare readonly hasListTarget: boolean
    declare readonly listTarget: HTMLOListElement

    private component: Component | null = null
    private knownIds = new Set<string>()

    private readonly onRender = (): void => {
        const rows = this.rows()
        const newRows = rows.filter((row) => !this.knownIds.has(row.dataset.historyId ?? ''))
        // The flash of the previous arrivals is over: only the new rows keep it.
        rows.forEach((row) => row.classList.toggle('game-history-new', newRows.includes(row)))
        this.rememberRows()
        this.scrollToNewest(newRows.length > 0 ? 'smooth' : 'auto')
    }

    override async connect() {
        this.rememberRows()
        this.scrollToNewest('auto')

        this.component = await getComponent(this.element)
        this.component.on('render:finished', this.onRender)
    }

    override disconnect() {
        this.component?.off('render:finished', this.onRender)
        this.component = null
    }

    open() {
        this.panelTarget.classList.add('show')
        this.scrollToNewest('auto')
    }

    close() {
        this.panelTarget.classList.remove('show')
    }

    private rows(): HTMLLIElement[] {
        return this.hasListTarget ? [...this.listTarget.querySelectorAll<HTMLLIElement>('[data-history-id]')] : []
    }

    private rememberRows() {
        this.knownIds = new Set(this.rows().map((row) => row.dataset.historyId ?? ''))
    }

    private scrollToNewest(behavior: ScrollBehavior) {
        if (this.hasListTarget) {
            this.listTarget.scrollTo({ top: this.listTarget.scrollHeight, behavior })
        }
    }
}
