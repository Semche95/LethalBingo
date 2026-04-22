import { Controller } from '@hotwired/stimulus'
import { initToasts, initTooltips } from '../utils'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLBodyElement> {
    override connect() {
        initTooltips(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        initToasts(document.querySelectorAll('.toast'))
    }
}
