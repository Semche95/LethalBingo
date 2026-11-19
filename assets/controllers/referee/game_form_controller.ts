import { Controller } from '@hotwired/stimulus'
import { ActionEvent } from '@hotwired/stimulus/dist/types/core/action_event'
import type TomSelect from 'tom-select'
import { focusFirstInput, getTomSelect, showToast } from '../../utils'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['team', 'round', 'form', 'hasCountdown', 'countdownHours', 'countdownMinutes']
    static override values = { playedRoundIdsByTeam: Object }
    declare readonly teamTarget: HTMLElement
    declare readonly roundTarget: HTMLElement
    declare readonly formTarget: HTMLFormElement
    declare readonly hasCountdownTarget: HTMLInputElement
    declare readonly countdownHoursTarget: HTMLInputElement
    declare readonly countdownMinutesTarget: HTMLInputElement
    // Round ids each team already has a game on, by team id.
    declare readonly playedRoundIdsByTeamValue: Record<string, number[]>

    // Rounds disabled by the server (incomplete bingos), whatever the team.
    private incompleteRoundIds = new Set<string>()

    override connect() {
        this.incompleteRoundIds = new Set(
            this.roundOptions()
                .filter((option) => option.disabled)
                .map((option) => option.value),
        )
        this.disablePlayedRounds()
        focusFirstInput(this.formTarget)
    }

    // Disables the rounds the chosen team already played, and clears the
    // chosen round if it is one of them.
    disablePlayedRounds() {
        const teamId = this.teamTarget.querySelector('select')?.value ?? ''
        const playedRoundIds = new Set((this.playedRoundIdsByTeamValue[teamId] ?? []).map(String))
        const select = this.roundTarget.querySelector('select')
        const toms = select ? getTomSelect(select) : undefined

        for (const option of this.roundOptions()) {
            if (option.value === '') {
                continue
            }

            const disabled = this.incompleteRoundIds.has(option.value) || playedRoundIds.has(option.value)
            option.disabled = disabled
            const tomsOption = toms?.options[option.value]
            if (tomsOption && tomsOption.disabled !== disabled) {
                toms?.updateOption(option.value, { ...tomsOption, disabled })
            }
        }

        if (select && playedRoundIds.has(select.value)) {
            if (toms) {
                toms.clear()
            } else {
                select.value = ''
            }
        }
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

    private roundOptions(): HTMLOptionElement[] {
        return Array.from(this.roundTarget.querySelectorAll('option'))
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
