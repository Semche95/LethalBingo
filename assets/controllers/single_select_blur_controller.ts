import { getTomSelect } from '../utils'
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    override connect() {
        this.element.addEventListener('change', this.blur)
    }

    override disconnect() {
        this.element.removeEventListener('change', this.blur)
    }

    blur = () => {
        getTomSelect(this.element)?.blur()
    }
}
