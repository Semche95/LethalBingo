import { Controller } from '@hotwired/stimulus'
import { ActionEvent } from '@hotwired/stimulus/dist/types/core/action_event'
import type TomSelect from 'tom-select'
import { focusFirstInput, getTomSelect, showToast } from '../../utils'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['team', 'form', 'hasCountdown', 'countdownHours', 'countdownMinutes']
    declare readonly teamTarget: HTMLElement
    declare readonly formTarget: HTMLFormElement
    declare readonly hasCountdownTarget: HTMLInputElement
    declare readonly countdownHoursTarget: HTMLInputElement
    declare readonly countdownMinutesTarget: HTMLInputElement

    override connect() {
        focusFirstInput(this.formTarget)
    }

    // Fills the countdown duration with one of the usual durations, which
    // implies a time limit.
    selectCountdownPreset({ params: { hours, minutes } }: ActionEvent) {
        this.hasCountdownTarget.checked = true
        this.countdownHoursTarget.value = String(hours)
        this.countdownMinutesTarget.value = String(minutes)
    }

    async copyGameLink({ params: { url } }: ActionEvent) {
        await navigator.clipboard.writeText(url)
        showToast('Lien copié')
    }

    onTeamAdded(event: Event) {
        const { id, name, members, flash } = (event as CustomEvent).detail

        if (flash) {
            showToast(flash.message, flash.type)
        }

        const select = this.teamTarget.querySelector('select')
        if (!select) {
            return
        }

        const text = members?.length > 0 ? `${name} (${members.join(', ')})` : name

        const selectTeam = (toms: TomSelect): void => {
            toms.addOption({ value: String(id), text })
            toms.setValue(String(id))
        }

        const toms = getTomSelect(select)
        if (toms) {
            selectTeam(toms)
            return
        }

        const observer = new MutationObserver(() => {
            const toms = getTomSelect(select)
            if (!toms) {
                return
            }

            observer.disconnect()
            selectTeam(toms)
        })

        observer.observe(this.teamTarget, { childList: true, subtree: true })
    }
}
