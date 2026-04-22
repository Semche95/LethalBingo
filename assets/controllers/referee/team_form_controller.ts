import { Component, getComponent } from '@symfony/ux-live-component'
import { ActionEvent } from '@hotwired/stimulus/dist/types/core/action_event'
import { Controller } from '@hotwired/stimulus'
import { Context } from '@hotwired/stimulus/dist/types/core/context'
import { focusFirstInput } from '../../utils'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLDivElement> {
    static override targets = ['captain']

    declare readonly captainTargets: HTMLInputElement[]

    private component!: Component
    private rowCount: number = 0

    constructor(context: Context) {
        super(context)
    }

    override async initialize() {
        this.component = await getComponent(this.element)

        // Focus the first input field in the form
        focusFirstInput(this.element)

        // Get initial row count
        const tableBody = this.element.querySelector('tbody')
        if (tableBody) {
            this.rowCount = tableBody.querySelectorAll('tr').length
        }

        this.component.on('render:finished', () => {
            // Check if a new row was added
            if (!tableBody) {
                return
            }

            const currentRowCount = tableBody.querySelectorAll('tr').length

            // If we have more rows than before, focus on the last added input
            if (currentRowCount > this.rowCount) {
                const rows = tableBody.querySelectorAll('tr')
                const lastRow = rows[rows.length - 1]
                const nameInput = lastRow.querySelector('input[type="text"]')

                if (nameInput) {
                    (nameInput as HTMLInputElement).focus()
                }
            }

            // Update row count for next render
            this.rowCount = currentRowCount
        })
    }

    async selectCaptain(event: ActionEvent) {
        for (const checkbox of this.captainTargets) {
            checkbox.checked = event.currentTarget === checkbox
            checkbox.dispatchEvent(new Event('change', { bubbles: true }))
        }
        await this.component.render()
    }
}
