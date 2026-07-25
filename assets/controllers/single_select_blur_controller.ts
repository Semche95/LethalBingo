import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    connect() {
        this.element.addEventListener('change', this.blur)
    }

    disconnect() {
        this.element.removeEventListener('change', this.blur)
    }

    blur = () => {
        const toms = (this.element as any).tomselect
        toms?.blur()
    }
}
