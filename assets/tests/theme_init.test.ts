import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import themeInitSource from '../../public/theme-init.js?raw'

type Listener = [EventTarget, string, EventListenerOrEventListenerObject]

let listeners: Listener[] = []
let systemDark = false
let systemListeners: Array<() => void> = []

const root = document.documentElement

function trackListeners(target: EventTarget): void {
    const original = target.addEventListener.bind(target)
    vi.spyOn(target, 'addEventListener').mockImplementation((type: string, listener: EventListenerOrEventListenerObject | null, options?: boolean | AddEventListenerOptions) => {
        if (listener) {
            listeners.push([target, type, listener])
            original(type, listener, options)
        }
    })
}

function runThemeInit(): void {
    new Function(themeInitSource)()
}

function setSystemDark(dark: boolean): void {
    systemDark = dark
    systemListeners.forEach((listener) => listener())
}

function choose(preference: unknown): void {
    document.dispatchEvent(new CustomEvent('app:theme', { detail: preference }))
}

beforeEach(() => {
    localStorage.clear()
    root.removeAttribute('data-bs-theme')
    root.removeAttribute('data-theme-preference')
    systemDark = false
    systemListeners = []
    vi.stubGlobal('matchMedia', () => ({
        get matches() { return systemDark },
        addEventListener: (_type: string, listener: () => void) => systemListeners.push(listener),
    }))
    trackListeners(document)
    trackListeners(window)
})

afterEach(() => {
    vi.restoreAllMocks()
    vi.unstubAllGlobals()
    listeners.forEach(([target, type, listener]) => target.removeEventListener(type, listener))
    listeners = []
})

describe('theme-init', () => {
    it('follows the system without a stored choice', () => {
        systemDark = true
        runThemeInit()

        expect(root.dataset.bsTheme).toBe('dark')
        expect(root.dataset.themePreference).toBe('auto')
    })

    it.each(['light', 'dark'])('applies the stored %s choice over the system', (theme) => {
        systemDark = theme === 'light'
        localStorage.setItem('theme', theme)
        runThemeInit()

        expect(root.dataset.bsTheme).toBe(theme)
        expect(root.dataset.themePreference).toBe(theme)
    })

    it('follows the system when the stored key is invalid', () => {
        localStorage.setItem('theme', 'sepia')
        runThemeInit()

        expect(root.dataset.bsTheme).toBe('light')
        expect(root.dataset.themePreference).toBe('auto')
    })

    it('follows the system when the storage throws', () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blocked') })
        systemDark = true

        expect(() => runThemeInit()).not.toThrow()
        expect(root.dataset.bsTheme).toBe('dark')
    })

    it('stores and applies an explicit choice from the switcher', () => {
        runThemeInit()
        choose('dark')

        expect(localStorage.getItem('theme')).toBe('dark')
        expect(root.dataset.bsTheme).toBe('dark')
        expect(root.dataset.themePreference).toBe('dark')
    })

    it('removes the stored key when the switcher goes back to the system', () => {
        localStorage.setItem('theme', 'light')
        systemDark = true
        runThemeInit()
        choose('auto')

        expect(localStorage.getItem('theme')).toBeNull()
        expect(root.dataset.bsTheme).toBe('dark')
        expect(root.dataset.themePreference).toBe('auto')
    })

    it('ignores an invalid choice from the switcher', () => {
        localStorage.setItem('theme', 'light')
        runThemeInit()
        choose('sepia')

        expect(localStorage.getItem('theme')).toBe('light')
        expect(root.dataset.themePreference).toBe('light')
    })

    it('follows a change of the system in auto mode only', () => {
        runThemeInit()
        setSystemDark(true)
        expect(root.dataset.bsTheme).toBe('dark')

        choose('light')
        setSystemDark(false)
        setSystemDark(true)
        expect(root.dataset.bsTheme).toBe('light')
    })

    it('applies a choice made in another tab', () => {
        runThemeInit()
        localStorage.setItem('theme', 'dark')
        window.dispatchEvent(new StorageEvent('storage', { key: 'theme', newValue: 'dark' }))

        expect(root.dataset.bsTheme).toBe('dark')
        expect(root.dataset.themePreference).toBe('dark')
    })

    it('ignores the storage events of other keys', () => {
        runThemeInit()
        localStorage.setItem('theme', 'dark')
        window.dispatchEvent(new StorageEvent('storage', { key: 'notes', newValue: 'visible' }))

        expect(root.dataset.themePreference).toBe('auto')
    })
})
