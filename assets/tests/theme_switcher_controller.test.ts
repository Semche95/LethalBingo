import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { Application } from '@hotwired/stimulus'
import ThemeSwitcherController from '../controllers/theme_switcher_controller'

let app: Application
let element: HTMLDivElement

const root = document.documentElement

function choice(preference: string): HTMLButtonElement {
    return element.querySelector<HTMLButtonElement>(`[data-theme-switcher-preference-param="${preference}"]`)!
}

async function flush(): Promise<void> {
    await Promise.resolve()
    await new Promise((resolve) => setTimeout(resolve, 0))
}

beforeEach(async () => {
    root.setAttribute('data-theme-preference', 'auto')
    element = document.createElement('div')
    element.setAttribute('data-controller', 'theme-switcher')
    element.innerHTML = ['light', 'dark', 'auto'].map((preference) => `
        <button type="button" role="menuitemradio" aria-checked="false"
                data-theme-switcher-target="choice"
                data-action="click->theme-switcher#choose"
                data-theme-switcher-preference-param="${preference}"></button>
    `).join('')
    document.body.appendChild(element)

    app = Application.start()
    app.register('theme-switcher', ThemeSwitcherController)
    await flush()
})

afterEach(() => {
    app.stop()
    element.remove()
    root.removeAttribute('data-theme-preference')
})

describe('ThemeSwitcherController', () => {
    it('sends the chosen preference with an app:theme event', () => {
        const received: unknown[] = []
        const listener = (event: Event) => received.push((event as CustomEvent).detail)
        document.addEventListener('app:theme', listener)

        choice('dark').click()
        document.removeEventListener('app:theme', listener)

        expect(received).toEqual(['dark'])
    })

    it('checks the choice matching the current preference', () => {
        expect(choice('auto').getAttribute('aria-checked')).toBe('true')
        expect(choice('dark').getAttribute('aria-checked')).toBe('false')
    })

    it('follows a preference applied elsewhere', async () => {
        root.setAttribute('data-theme-preference', 'light')
        await flush()

        expect(choice('light').getAttribute('aria-checked')).toBe('true')
        expect(choice('auto').getAttribute('aria-checked')).toBe('false')
    })
})
