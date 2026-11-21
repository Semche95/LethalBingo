import { Controller } from '@hotwired/stimulus'

/**
 * Menu of the theme: sends the chosen preference to public/theme-init.js, which alone
 * stores and applies it, and checks the choice matching data-theme-preference on <html>.
 */
export default class extends Controller {
    static override targets = ['choice']

    declare readonly choiceTargets: HTMLElement[]

    private readonly observer = new MutationObserver(() => this.syncChoices())

    override connect(): void {
        this.syncChoices()
        this.observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme-preference'] })
    }

    override disconnect(): void {
        this.observer.disconnect()
    }

    choose({ params }: { params: { preference: string } }): void {
        document.dispatchEvent(new CustomEvent('app:theme', { detail: params.preference }))
    }

    private syncChoices(): void {
        const preference = document.documentElement.dataset.themePreference
        this.choiceTargets.forEach((choice) => {
            const param = choice.dataset.themeSwitcherPreferenceParam
            choice.setAttribute('aria-checked', String(param === preference))
        })
    }
}
