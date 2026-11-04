import { Modal, Tooltip } from 'bootstrap'
import type TomSelect from 'tom-select'

const TOAST_DELAY = 4000
const TOAST_ANIMATION_DURATION = 250

const TOOLTIP_SELECTOR = '[data-bs-toggle="tooltip"]'

// Elements given a tooltip by initTooltips(), so that the ones a render removes
// can be disposed, and the title each one shows, to follow `data-bs-title`
// when a render changes it.
const tooltipElements = new Set<HTMLElement>()
const tooltipTitles = new WeakMap<HTMLElement, string>()
// Whether the tooltip is on screen or fading in or out: dispose() during a fade
// throws at its end, so such a tooltip is hidden first and disposed once gone.
// The listeners live as long as the document, so they are never removed.
const visibleTooltips = new WeakSet<HTMLElement>()

document.addEventListener('show.bs.tooltip', event => visibleTooltips.add(event.target as HTMLElement))
document.addEventListener('hidden.bs.tooltip', event => visibleTooltips.delete(event.target as HTMLElement))

// The tooltips open on hover only, so that a clicked button, which keeps the
// focus, does not keep its tooltip: the keyboard focus (`:focus-visible`, never
// given by a click) opens them here instead.
document.addEventListener('focusin', event => {
    const element = event.target
    if (element instanceof HTMLElement && element.matches(TOOLTIP_SELECTOR) && element.matches(':focus-visible')) {
        Tooltip.getInstance(element)?.show()
    }
})
document.addEventListener('focusout', event => {
    const element = event.target
    if (element instanceof HTMLElement && element.matches(TOOLTIP_SELECTOR)) {
        Tooltip.getInstance(element)?.hide()
    }
})

export function initTooltips(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>(TOOLTIP_SELECTOR).forEach(element => {
        const tooltip = Tooltip.getOrCreateInstance(element, { trigger: 'hover' })
        tooltipElements.add(element)

        const title = element.getAttribute('data-bs-title')
        if (title === null) {
            return
        }
        const shownTitle = tooltipTitles.get(element)
        if (shownTitle !== undefined && shownTitle !== title) {
            tooltip.setContent({ '.tooltip-inner': title })
        }
        tooltipTitles.set(element, title)
    })
}

export function setTooltipTitle(element: HTMLElement, title: string): void {
    element.setAttribute('data-bs-title', title)
    tooltipTitles.set(element, title)
    Tooltip.getInstance(element)?.setContent({ '.tooltip-inner': title })
}

export function hideTooltip(element: HTMLElement): void {
    Tooltip.getInstance(element)?.hide()
}

// After a render of a Live Component, which keeps the elements that did not
// change: only the tooltips of the removed elements go, the others stay.
export function syncTooltips(root: HTMLElement): void {
    tooltipElements.forEach(element => {
        if (!element.isConnected || !element.matches(TOOLTIP_SELECTOR)) {
            disposeTooltip(element)
        }
    })
    initTooltips(root)
}

// hide(), unlike dispose(), can stop a tooltip during its fade.
export function hideTooltips(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>(TOOLTIP_SELECTOR).forEach(element => Tooltip.getInstance(element)?.hide())
}

export function disposeTooltips(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>(TOOLTIP_SELECTOR).forEach(disposeTooltip)
}

function disposeTooltip(element: HTMLElement): void {
    tooltipElements.delete(element)
    tooltipTitles.delete(element)
    const tooltip = Tooltip.getInstance(element)
    if (!tooltip) {
        return
    }
    if (!visibleTooltips.has(element)) {
        tooltip.dispose()
        return
    }
    // Listened on the element: once removed from the page, its events no longer reach the document.
    element.addEventListener('hidden.bs.tooltip', () => {
        visibleTooltips.delete(element)
        tooltip.dispose()
    }, { once: true })
    tooltip.hide()
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
