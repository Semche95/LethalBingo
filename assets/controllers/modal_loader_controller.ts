import { Controller } from '@hotwired/stimulus'
import { Modal } from 'bootstrap'

export default class extends Controller<HTMLAnchorElement> {
    static override values = {
        target: { type: String, default: 'add-team-modal' },
        title: { type: String, default: '' },
    }

    declare readonly targetValue: string
    declare readonly titleValue: string

    override connect(): void {
        document.addEventListener('team:added', this.close)
        document.addEventListener('team:updated', this.close)
    }

    override disconnect(): void {
        document.removeEventListener('team:added', this.close)
        document.removeEventListener('team:updated', this.close)
    }

    async open(event: Event): Promise<void> {
        event.preventDefault()

        const modalEl = document.getElementById(this.targetValue)
        if (!modalEl) return

        const response = await fetch(this.element.href, {
            headers: {
                'X-Fragment-Request': '1',
            },
        })

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
        }
        modalEl.addEventListener('hidden.bs.modal', onHidden)
    }

    close = (): void => {
        const modalEl = document.getElementById(this.targetValue)
        if (!modalEl) return

        const modal = Modal.getInstance(modalEl)
        modal?.hide()
    }
}
