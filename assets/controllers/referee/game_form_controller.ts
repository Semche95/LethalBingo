import { Controller } from '@hotwired/stimulus'
import { ActionEvent } from '@hotwired/stimulus/dist/types/core/action_event'
import { focusFirstInput, showToast } from '../../utils'
import { Modal } from 'bootstrap'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['team']
    declare readonly teamTarget: HTMLElement

    override connect() {
        focusFirstInput(this.element)
        document.addEventListener('team:added', this.onTeamAdded)
        document.addEventListener('team:updated', this.onTeamAdded)
    }

    override disconnect() {
        document.removeEventListener('team:added', this.onTeamAdded)
        document.removeEventListener('team:updated', this.onTeamAdded)
    }

    async copyGameLink({ params: { url } }: ActionEvent) {
        await navigator.clipboard.writeText(url)
    }

    openRegenerateSlugModal() {
        const modal = document.getElementById('regenerate-slug-modal')
        if (modal) {
            const bootstrapModal = new Modal(modal)
            bootstrapModal.show()
        }
    }

    onTeamAdded = (event: Event) => {
        const { id, name, members, flash } = (event as CustomEvent).detail

        const modalEl = document.getElementById('add-team-modal')
        if (modalEl) {
            const bsModal = Modal.getInstance(modalEl)
            bsModal?.hide()
        }

        if (flash) {
            showToast(flash.message, flash.type)
        }

        const select = this.teamTarget.querySelector('select')
        if (!select) {
            return
        }

        const text = members?.length > 0 ? `${name} (${members.join(', ')})` : name

        const toms = (select as any).tomselect
        if (toms) {
            toms.addOption({ value: String(id), text })
            toms.setValue(String(id))
            return
        }

        const observer = new MutationObserver(() => {
            const toms = (select as any).tomselect
            if (!toms) {
                return
            }

            observer.disconnect()
            toms.addOption({ value: String(id), text })
            toms.setValue(String(id))
        })

        observer.observe(this.teamTarget, { childList: true, subtree: true })
    }
}
