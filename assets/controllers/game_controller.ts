import { Controller } from '@hotwired/stimulus'
import { initTooltips } from '../utils'
import { ActionEvent } from '@hotwired/stimulus/dist/types/core/action_event'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['bingo']

    private boundCardUpdated!: OmitThisParameter<() => void>

    override initialize() {
        this.boundCardUpdated = this.cardUpdated.bind(this)
    }

    override connect() {
        document.addEventListener('bingo-card:updated', this.boundCardUpdated)
    }

    override disconnect() {
        document.removeEventListener('bingo-card:updated', this.boundCardUpdated)
    }

    cardUpdated() {
        console.log('cardUpdated')
        this.refreshTooltips()
    }

    refreshTooltips() {
        document.querySelectorAll('.tooltip[role="tooltip"]').forEach((elem) => elem.remove())
        initTooltips(this.element.querySelectorAll('[data-bs-toggle="tooltip"]'))
    }

    scrollToSquare(event: ActionEvent) {
        event.preventDefault()

        const row = ~~event?.params?.row
        const col = ~~event?.params?.col

        window.scrollTo(0, 0)

        const targetDiv = document.getElementById('square' + String(row) + String(col))
        if (!targetDiv) {
            return
        }
        setTimeout(() => {
            targetDiv.classList.add('blink')
            setTimeout(() => {
                targetDiv.classList.remove('blink')
            }, 1000)
        }, 400)
    }
}
