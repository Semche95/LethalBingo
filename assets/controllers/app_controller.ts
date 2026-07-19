import { Controller } from '@hotwired/stimulus'
import { initToasts, initTooltips } from '../utils'
import { Toast, Tooltip } from 'bootstrap'

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
    }

    private disposeTooltips(): void {
        document.querySelectorAll<HTMLElement>('[data-bs-toggle="tooltip"]').forEach((element) => {
            const instance = Tooltip.getInstance(element)
            if (instance) {
                instance.dispose()
            }
        })
    }

    private disposeToasts(): void {
        document.querySelectorAll<HTMLElement>('.toast').forEach((element) => {
            const instance = Toast.getInstance(element)
            if (instance) {
                instance.dispose()
            }
        })
    }

    private onBeforeCache(): void {
        this.disposeTooltips()
        this.disposeToasts()
    }

    private onRender(): void {
        initTooltips(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        initToasts(document.querySelectorAll('.toast'))
    }
}
