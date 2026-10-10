import type { Component } from '@symfony/ux-live-component'

const nameCheck = /^[-_a-zA-Z0-9]{4,22}$/
const tokenCheck = /^[-_\/+a-zA-Z0-9]{24,}$/
const csrfFieldSelector = 'input[data-controller="csrf-protection"], input[name="_csrf_token"]'

function randomCsrfToken(): string {
    return btoa(String.fromCharCode(...window.crypto.getRandomValues(new Uint8Array(18))))
}

function writeCsrfCookie(csrfCookie: string, csrfToken: string, value: string, extra: string = ''): void {
    const cookie = csrfCookie + '_' + csrfToken + '=' + value + '; path=/; samesite=strict' + extra
    document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie
}

// Generate and double-submit a CSRF token in a form field and a cookie, as defined by Symfony's SameOriginCsrfTokenManager
document.addEventListener('submit', function (event: SubmitEvent) {
    generateCsrfToken(event.target as HTMLFormElement)
}, true)

// Live Components post their own requests instead of submitting the form: double-submit
// one token per component with each of them, sent in place of the rendered token id.
// The listener lives as long as the document, so it is never removed.
document.addEventListener('live:connect', (event: Event) => {
    const { component } = (event as CustomEvent<{ component: Component }>).detail
    const csrfToken: string = randomCsrfToken()

    component.on('request:started', (requestConfig: { updated: Record<string, unknown> }) => {
        const csrfField = component.element.querySelector<HTMLInputElement>('input[data-controller="csrf-protection"]')

        // The submit listener above swaps the token id for a token on the field itself
        const csrfCookie: string | undefined = csrfField?.getAttribute('data-csrf-protection-cookie-value') ?? csrfField?.value

        if (!csrfField || !csrfCookie || !nameCheck.test(csrfCookie)) {
            return
        }

        writeCsrfCookie(csrfCookie, csrfToken, csrfCookie)
        // change_password_form[_token] is the change_password_form._token model
        requestConfig.updated[csrfField.name.replaceAll(']', '').replaceAll('[', '.')] = csrfToken
    })
})

export function generateCsrfToken(formElement: HTMLFormElement): void {
    const csrfField = formElement.querySelector<HTMLInputElement>(csrfFieldSelector)

    if (!csrfField) {
        return
    }

    let csrfCookie: string | null = csrfField.getAttribute('data-csrf-protection-cookie-value')
    let csrfToken: string = csrfField.value

    if (!csrfCookie && nameCheck.test(csrfToken)) {
        csrfField.setAttribute('data-csrf-protection-cookie-value', csrfCookie = csrfToken)
        csrfField.defaultValue = csrfToken = randomCsrfToken()
        csrfField.dispatchEvent(new Event('change', { bubbles: true }))
    }

    if (csrfCookie && tokenCheck.test(csrfToken)) {
        writeCsrfCookie(csrfCookie, csrfToken, csrfCookie)
    }
}

export function generateCsrfHeaders(formElement: HTMLFormElement): Record<string, string> {
    const headers: Record<string, string> = {}
    const csrfField = formElement.querySelector<HTMLInputElement>(csrfFieldSelector)

    if (!csrfField) {
        return headers
    }

    const csrfCookie: string | null = csrfField.getAttribute('data-csrf-protection-cookie-value')

    if (csrfCookie && tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        headers[csrfCookie] = csrfField.value
    }

    return headers
}

export function removeCsrfToken(formElement: HTMLFormElement): void {
    const csrfField = formElement.querySelector<HTMLInputElement>(csrfFieldSelector)

    if (!csrfField) {
        return
    }

    const csrfCookie: string | null = csrfField.getAttribute('data-csrf-protection-cookie-value')

    if (csrfCookie && tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        writeCsrfCookie(csrfCookie, csrfField.value, '0', '; max-age=0')
    }
}

/* stimulusFetch: 'lazy' */
export default 'csrf-protection-controller'
