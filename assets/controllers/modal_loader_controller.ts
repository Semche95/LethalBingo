import { Controller } from '@hotwired/stimulus'
import { Modal } from 'bootstrap'
import { hideModal } from '../utils'

export default class extends Controller<HTMLAnchorElement> {
    static override values = {
        target: { type: String, default: 'add-team-modal' },
        title: { type: String, default: '' },
        autoOpen: { type: Boolean, default: false },
        closeUrl: { type: String, default: '' },
    }

    declare readonly targetValue: string
    declare readonly titleValue: string
    declare readonly autoOpenValue: boolean
    declare readonly closeUrlValue: string

    override connect(): void {
        if (this.autoOpenValue) void this.open()
    }

    async open(event?: Event): Promise<void> {
        event?.preventDefault()

        const modalEl = document.getElementById(this.targetValue)
        if (!modalEl) return

        const response = await fetch(this.element.href, {
            headers: {
                'X-Fragment-Request': '1',
            },
        })
        if (!response.ok) return

        const html = await response.text()
        const modalBody = modalEl.querySelector('.modal-body')
        if (modalBody) {
            modalBody.innerHTML = html
        }

        if (this.titleValue) {
            const titleEl = modalEl.querySelector('.modal-title')
            if (titleEl) titleEl.textContent = this.titleValue
        }

        Modal.getOrCreateInstance(modalEl).show()

        const onHidden = (event: Event): void => {
            if (event.target !== modalEl) return
            modalEl.removeEventListener('hidden.bs.modal', onHidden)
            if (modalBody) modalBody.innerHTML = ''
            // Keeps Turbo's history state so back/forward still work.
            if (this.closeUrlValue) history.replaceState(history.state, '', this.closeUrlValue)
        }
        modalEl.addEventListener('hidden.bs.modal', onHidden)
    }

    close(): void {
        hideModal(this.targetValue)
    }
}
