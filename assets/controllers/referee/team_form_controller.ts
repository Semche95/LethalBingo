import { Component, getComponent } from '@symfony/ux-live-component'
import { Controller } from '@hotwired/stimulus'
import { Context } from '@hotwired/stimulus/dist/types/core/context'
import { focusFirstInput } from '../../utils'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['checkbox', 'button']

    declare readonly checkboxTargets: HTMLInputElement[]
    declare readonly buttonTargets: HTMLButtonElement[]

    private component!: Component
    private rowCount: number = 0

    constructor(context: Context) {
        super(context)
    }

    override async initialize() {
        this.component = await getComponent(this.element)

        focusFirstInput(this.element)

        const tableBody = this.element.querySelector('tbody')
        if (tableBody) {
            this.rowCount = tableBody.querySelectorAll('tr').length
        }

        this.component.on('render:finished', () => {
            if (!tableBody) {
                return
            }

            const currentRowCount = tableBody.querySelectorAll('tr').length

            if (currentRowCount > this.rowCount) {
                const rows = tableBody.querySelectorAll('tr')
                const lastRow = rows[rows.length - 1]
                const nameInput = lastRow.querySelector('input[type="text"]')

                if (nameInput) {
                    (nameInput as HTMLInputElement).focus()
                }
            }

            this.rowCount = currentRowCount
        })
    }

    selectCaptain(event: Event): void {
        const clickedButton = event.currentTarget as HTMLButtonElement
        const index = this.buttonTargets.indexOf(clickedButton)
        if (index === -1) return

        if (this.checkboxTargets[index].checked) {
            return
        }

        this.checkboxTargets.forEach((checkbox, i) => {
            const checked = i === index

            if (checkbox.checked !== checked) {
                checkbox.checked = checked
                checkbox.dispatchEvent(new Event('change', { bubbles: true }))
            }
        })

        this.buttonTargets.forEach((button, i) => {
            button.classList.toggle('btn-captain-active', i === index)
        })
    }
}
