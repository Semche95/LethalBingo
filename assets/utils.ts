import { Toast, Tooltip } from 'bootstrap'

export function initTooltips(elements: NodeListOf<HTMLElement>) {
    [...elements].map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl))
}

export function initToasts(elements: NodeListOf<HTMLElement>) {
    [...elements].map(toastTriggerEl => new Toast(toastTriggerEl).show())
}

export function focusFirstInput(container: HTMLElement) {
    const focusableElements = container.querySelectorAll<HTMLElement>(
        'input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
    )

    if (focusableElements.length > 0) {
        focusableElements[0].focus()
    }
}
