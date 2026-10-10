import { Modal, Tooltip } from 'bootstrap'
import type TomSelect from 'tom-select'

const TOAST_DELAY = 4000
const TOAST_ANIMATION_DURATION = 250

const TOOLTIP_SELECTOR = '[data-bs-toggle="tooltip"]'

export function initTooltips(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>(TOOLTIP_SELECTOR).forEach(element => new Tooltip(element))
}

export function disposeTooltips(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>(TOOLTIP_SELECTOR).forEach(element => Tooltip.getInstance(element)?.dispose())
}

export function showModal(id: string): void {
    const element = document.getElementById(id)
    if (element) Modal.getOrCreateInstance(element).show()
}

export function hideModal(id: string): void {
    const element = document.getElementById(id)
    if (element) Modal.getInstance(element)?.hide()
}

export function getTomSelect(element: Element): TomSelect | undefined {
    return (element as Element & { tomselect?: TomSelect }).tomselect
}

export function initToasts(elements: NodeListOf<HTMLElement>) {
    elements.forEach((toast) => initToast(toast))
}

function initToast(toast: HTMLElement): void {
    requestAnimationFrame(() => {
        toast.classList.add('toast-visible')
    })

    toast.addEventListener('click', (event) => {
        if ((event.target as HTMLElement).closest('.btn-close')) {
            dismissToast(toast)
        }
    })

    setTimeout(() => dismissToast(toast), TOAST_DELAY)
}

function dismissToast(toast: HTMLElement): void {
    if (toast.dataset.dismissing === 'true') return
    toast.dataset.dismissing = 'true'

    toast.classList.remove('toast-visible')
    setTimeout(() => toast.remove(), TOAST_ANIMATION_DURATION)
}

export function showToast(message: string, type: string = 'success'): void {
    const container = document.querySelector('.toast-container')
    if (!container) {
        return
    }

    const toast = document.createElement('div')
    toast.className = `toast show align-items-center toast-outline toast-outline-${type}`
    toast.setAttribute('role', 'alert')
    toast.setAttribute('aria-live', 'assertive')
    toast.setAttribute('aria-atomic', 'true')
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-progress">
            <div class="toast-progress-bar toast-progress-bar-${type}"></div>
        </div>
    `
    container.appendChild(toast)
    initToast(toast)
}

export function focusFirstInput(container: HTMLElement) {
    const focusableElements = container.querySelectorAll<HTMLElement>(
        'input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
    )

    if (focusableElements.length > 0) {
        focusableElements[0].focus()
    }
}

// Delays fn until `delayMs` have passed without a new call; cancel() drops the pending call.
export function debounce(fn: () => void, delayMs: number): (() => void) & { cancel: () => void } {
    let timeoutId: number | null = null

    const cancel = (): void => {
        if (timeoutId !== null) {
            window.clearTimeout(timeoutId)
            timeoutId = null
        }
    }

    return Object.assign(() => {
        cancel()
        timeoutId = window.setTimeout(fn, delayMs)
    }, { cancel })
}
