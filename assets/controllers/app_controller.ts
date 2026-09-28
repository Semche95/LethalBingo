import { Controller } from '@hotwired/stimulus'
import { initToasts, initTooltips } from '../utils'
import { Tooltip } from 'bootstrap'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLBodyElement> {
    private boundOnBeforeCache: (() => void) | null = null
    private boundOnRender: (() => void) | null = null

    override connect() {
        initTooltips(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        initToasts(document.querySelectorAll('.toast'))

        this.boundOnBeforeCache = this.onBeforeCache.bind(this)
        this.boundOnRender = this.onRender.bind(this)
        document.addEventListener('turbo:before-cache', this.boundOnBeforeCache)
        document.addEventListener('turbo:render', this.boundOnRender)

        performance.addEventListener('resourcetimingbufferfull', this.onResourceTimingBufferFull)
    }

    override disconnect() {
        if (this.boundOnBeforeCache) {
            document.removeEventListener('turbo:before-cache', this.boundOnBeforeCache)
            this.boundOnBeforeCache = null
        }
        if (this.boundOnRender) {
            document.removeEventListener('turbo:render', this.boundOnRender)
            this.boundOnRender = null
        }

        performance.removeEventListener('resourcetimingbufferfull', this.onResourceTimingBufferFull)
    }

    // The browser keeps a limited list of received responses (about 250) and
    // silently stops recording once it is full, which Vite fills quickly in dev.
    // The game timer reads the latest responses to sync with the server clock
    // (game_timer_anchor.ts), so clearing lets the browser keep recording them.
    private readonly onResourceTimingBufferFull = (): void => {
        performance.clearResourceTimings()
    }

    private disposeTooltips(): void {
        document.querySelectorAll<HTMLElement>('[data-bs-toggle="tooltip"]').forEach((element) => {
            const instance = Tooltip.getInstance(element)
            if (instance) {
                instance.dispose()
            }
        })
    }

    private onBeforeCache(): void {
        this.disposeTooltips()
        document.querySelectorAll<HTMLElement>('.toast').forEach(el => el.remove())
    }

    private onRender(): void {
        initTooltips(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        initToasts(document.querySelectorAll('.toast'))
    }
}
