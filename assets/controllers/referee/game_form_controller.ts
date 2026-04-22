import { Controller } from '@hotwired/stimulus'
import { ActionEvent } from '@hotwired/stimulus/dist/types/core/action_event'
import { focusFirstInput } from '../../utils'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    override connect() {
        // Focus the first input field in the form
        focusFirstInput(this.element)
    }

    async copyGameLink({ params: { url } }: ActionEvent) {
        await navigator.clipboard.writeText(url)
    }
}
