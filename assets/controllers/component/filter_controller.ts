import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    static override targets = ['round', 'season']

    declare readonly roundTarget: HTMLSelectElement
    declare readonly seasonTarget: HTMLSelectElement

    reset() {
        this.roundTarget.value = ''
        this.seasonTarget.value = ''

        this.roundTarget.dispatchEvent(new Event('change', { bubbles: true }))
        this.seasonTarget.dispatchEvent(new Event('change', { bubbles: true }))
    }
}
