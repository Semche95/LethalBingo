import { Controller } from '@hotwired/stimulus'
import { getComponent } from '@symfony/ux-live-component'

export default class extends Controller<HTMLElement> {
    async reset(event: Event): Promise<void> {
        event.preventDefault()

        const filterElement = this.element.closest<HTMLElement>('[data-controller="live"]')
        if (!filterElement?.parentElement) return

        const parentElement = filterElement.parentElement.closest<HTMLElement>('[data-controller="live"]')
        if (!parentElement) return

        const childComponent = await getComponent(filterElement)
        const parentComponent = await getComponent(parentElement)

        const defaults: Record<string, string | null> = {
            round: null,
            season: null,
            team: null,
            started: null,
            ended: null,
        }

        // --- Optimistic client-side reset ---
        //
        // This block MUST stay. Without it, there is a race condition: the server-side
        // reset (parentComponent.action('resetFilters')) only propagates back to the
        // child Filter component after a full round-trip (parent re-renders →
        // updateFromParent on child). Until that response arrives, the child's
        // internal valueStore still holds the previous filter values.
        //
        // If the user selects a new filter during that window, the data-model
        // binding reads the stale valueStore and sends the obsolete state along
        // with the new selection — causing previously-cleared filters to
        // reappear.
        //
        // This optimistic reset clears both the child's valueStore and the <select>
        // DOM before the server round-trip, eliminating the race window.
        for (const [model, value] of Object.entries(defaults)) {
            if (childComponent.valueStore.has(model)) {
                childComponent.set(model, value, false)
            }
        }

        const selects = filterElement.querySelectorAll<HTMLSelectElement>('select[data-model]')
        selects.forEach(select => {
            select.value = select.querySelector('option')?.value ?? ''
        })
        // --- End optimistic reset ---

        parentComponent.action('resetFilters')
    }
}
