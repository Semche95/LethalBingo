import { Controller } from '@hotwired/stimulus'
import { initTooltips } from '../../utils'
import { ActionEvent } from '@hotwired/stimulus/dist/types/core/action_event'
import { Component, getComponent } from '@symfony/ux-live-component'
import { watchWakeUps } from '../../wake_up'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['bingo']
    static override values = {
        mercureUrl: String,
    }

    declare readonly mercureUrlValue: string
    private component!: Component
    private es!: EventSource
    // Watched here rather than in the timer, which is gone once the game is over.
    private stopWakeUpWatch: (() => void) | null = null

    override async initialize() {
        this.component = await getComponent(this.element)
    }

    private readonly onWakeUp = (): void => {
        this.reconnect()
    }

    override connect() {
        this.openEventSource()

        this.stopWakeUpWatch = watchWakeUps(this.onWakeUp)
    }

    override disconnect() {
        this.es.close()

        this.stopWakeUpWatch?.()
        this.stopWakeUpWatch = null
    }

    private openEventSource() {
        let hasBeenOpen = false

        this.es = new EventSource(this.mercureUrlValue)
        this.es.onopen = () => {
            // The browser reconnected by itself: messages sent meanwhile are lost.
            if (hasBeenOpen) {
                this.refreshGame()
            }
            hasBeenOpen = true
        }
        this.es.onmessage = async (event) => {
            const eventData = JSON.parse(event.data)
            this.component.emit(eventData.event, { serverTime: eventData.serverTime })
        }
    }

    // After a sleep or a network loss the connection may be dead without the
    // browser noticing, and messages sent meanwhile (pause, stop, validated
    // square...) were missed: reopen it first, then reload the whole game state.
    private reconnect() {
        this.es.close()
        this.openEventSource()
        this.refreshGame()
    }

    private refreshGame() {
        this.component?.emit('gameFrameRefresh', {})
    }

    // TODO
    refreshTooltips() {
        document.querySelectorAll('.tooltip[role="tooltip"]').forEach((elem) => elem.remove())
        initTooltips(this.element)
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
