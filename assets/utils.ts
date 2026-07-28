import { Tooltip } from 'bootstrap'

const TOAST_DELAY = 4000
const TOAST_ANIMATION_DURATION = 250

export function initTooltips(elements: NodeListOf<HTMLElement>) {
    [...elements].map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl))
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
    toast.classList.add('toast-slide-out')
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
