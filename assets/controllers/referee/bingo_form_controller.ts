import { Controller } from '@hotwired/stimulus'
import { initTooltips, focusFirstInput } from '../../utils'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['bingo']

    declare readonly bingoTarget: HTMLDivElement

    override connect() {
        // Focus the first input field in the form
        focusFirstInput(this.element)
    }

    bingoTargetConnected() {
        initTooltips(this.bingoTarget)
    }
}
