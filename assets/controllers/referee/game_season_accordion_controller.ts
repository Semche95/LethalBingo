import { Controller } from '@hotwired/stimulus'
import { visit } from '@hotwired/turbo'

const COOKIE_NAME = 'referee_game_seasons_open'
const COOKIE_MAX_AGE = 60 * 60 * 24 * 30

export default class extends Controller<HTMLElement> {
    static override targets = ['panel']
    declare readonly panelTargets: HTMLElement[]

    override connect(): void {
        for (const panel of this.panelTargets) {
            panel.addEventListener('shown.bs.collapse', () => this.persistCookie())
            panel.addEventListener('hidden.bs.collapse', () => this.persistCookie())
        }

        this.element.addEventListener('click', (event: Event) => {
            const cell = (event.target as HTMLElement).closest<HTMLElement>('[data-game-url]')
            if (!cell) return

            const url = cell.dataset.gameUrl
            if (url) visit(url)
        })
    }

    private persistCookie(): void {
        const open = this.panelTargets
            .filter(s => s.classList.contains('show'))
            .map(s => s.dataset.season ?? '')
            .filter(Boolean)

        document.cookie = `${COOKIE_NAME}=${open.join(',')}; path=/; max-age=${COOKIE_MAX_AGE}; SameSite=Lax`
    }
}
