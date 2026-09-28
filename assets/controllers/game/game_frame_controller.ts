import { Controller } from '@hotwired/stimulus'
import { initTooltips } from '../../utils'
import { ActionEvent } from '@hotwired/stimulus/dist/types/core/action_event'
import { Component, getComponent } from '@symfony/ux-live-component'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['bingo']
    static override values = {
        mercureUrl: String,
    }

    declare readonly mercureUrlValue: string
    private component!: Component
    private es!: EventSource

    override async initialize() {
        this.component = await getComponent(this.element)
    }

    override connect() {
        this.es = new EventSource(this.mercureUrlValue)
        this.es.onmessage = async (event) => {
            const eventData = JSON.parse(event.data)
            this.component.emit(eventData.event, { serverTime: eventData.serverTime })
        }
    }

    override disconnect() {
        this.es.close()
    }

    // TODO
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
