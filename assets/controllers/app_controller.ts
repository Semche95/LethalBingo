import { Controller } from '@hotwired/stimulus'
import { disposeTooltips, initToasts, initTooltips } from '../utils'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLBodyElement> {
    private boundOnBeforeCache: (() => void) | null = null
    private boundOnRender: (() => void) | null = null

    override connect() {
        this.onRender()

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

    private onBeforeCache(): void {
        disposeTooltips()
        document.querySelectorAll<HTMLElement>('.toast').forEach(el => el.remove())
    }

    private onRender(): void {
        initTooltips()
        initToasts(document.querySelectorAll('.toast'))
    }
}
