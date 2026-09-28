import { describe, it, expect } from 'vitest'
import { restoreHiddenNonces } from '../turbo_nonce'

function script(attribute: string | null, property: string): HTMLScriptElement {
    const element = document.createElement('script')
    if (attribute !== null) element.setAttribute('nonce', attribute)
    Object.defineProperty(element, 'nonce', { value: property })
    return element
}

describe('restoreHiddenNonces', () => {
    it('writes a hidden nonce back into the attribute', () => {
        const body = document.createElement('body')
        body.append(script('', 'abc'))

        restoreHiddenNonces(body)

        expect(body.querySelector('script')?.getAttribute('nonce')).toBe('abc')
    })

    it('leaves visible nonces and scripts without nonce alone', () => {
        const body = document.createElement('body')
        body.append(script('visible', 'other'), script(null, ''))

        restoreHiddenNonces(body)

        const [visible, none] = body.querySelectorAll('script')
        expect(visible.getAttribute('nonce')).toBe('visible')
        expect(none.hasAttribute('nonce')).toBe(false)
    })
})
