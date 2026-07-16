import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    static override targets = ['round', 'season', 'team', 'started', 'ended']

    declare readonly roundTarget: HTMLSelectElement
    declare readonly seasonTarget: HTMLSelectElement
    declare readonly hasTeamTarget: boolean
    declare readonly teamTarget?: HTMLSelectElement
    declare readonly hasStartedTarget: boolean
    declare readonly startedTarget?: HTMLSelectElement
    declare readonly hasEndedTarget: boolean
    declare readonly endedTarget?: HTMLSelectElement

    reset() {
        this.roundTarget.value = ''
        this.seasonTarget.value = ''

        this.roundTarget.dispatchEvent(new Event('change', { bubbles: true }))
        this.seasonTarget.dispatchEvent(new Event('change', { bubbles: true }))

        // Reset additional filters if they exist (only in game list context)
        if (this.hasTeamTarget) {
            this.teamTarget!.value = ''
            this.teamTarget!.dispatchEvent(new Event('change', { bubbles: true }))
        }

        if (this.hasStartedTarget) {
            this.startedTarget!.value = 'null'
            this.startedTarget!.dispatchEvent(new Event('change', { bubbles: true }))
        }

        if (this.hasEndedTarget) {
            this.endedTarget!.value = 'null'
            this.endedTarget!.dispatchEvent(new Event('change', { bubbles: true }))
        }
    }
}
