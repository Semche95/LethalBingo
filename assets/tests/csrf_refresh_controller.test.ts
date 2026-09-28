import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { Application } from '@hotwired/stimulus'
import { visit } from '@hotwired/turbo'
import CsrfRefreshController from '../controllers/csrf_refresh_controller'

vi.mock('@hotwired/turbo', () => ({ visit: vi.fn() }))

let app: Application
let element: HTMLDivElement
let fetchMock: ReturnType<typeof vi.fn>

function mockResponse(tokens: Record<string, string>, init: { status?: number, redirected?: boolean } = {}) {
    const status = init.status ?? 200
    fetchMock.mockResolvedValue({ ok: status < 300, status, redirected: init.redirected ?? false, json: async () => tokens })
}

function addTokenInput(tokenId: string, value = 'old'): HTMLInputElement {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.value = value
    input.dataset.csrfTokenId = tokenId
    document.body.appendChild(input)

    return input
}

async function connectController(interval?: number) {
    element = document.createElement('div')
    element.setAttribute('data-controller', 'csrf-refresh')
    element.setAttribute('data-csrf-refresh-url-value', '/csrf-tokens')
    element.setAttribute('data-csrf-refresh-login-url-value', '/user/login')
    if (interval !== undefined) {
        element.setAttribute('data-csrf-refresh-interval-value', String(interval))
    }
    document.body.appendChild(element)
    await Promise.resolve()
}

async function flush() {
    for (let i = 0; i < 5; i++) {
        await Promise.resolve()
    }
}

function requestedIds(call = 0): string[] {
    return new URL(fetchMock.mock.calls[call][0]).searchParams.getAll('ids[]')
}

function reportedIdle(call = 0): string | null {
    return new URL(fetchMock.mock.calls[call][0]).searchParams.get('idle')
}

async function ping() {
    window.dispatchEvent(new Event('online'))
    await flush()
}

beforeEach(() => {
    fetchMock = vi.fn()
    mockResponse({})
    vi.stubGlobal('fetch', fetchMock)
    app = Application.start(document.documentElement)
    app.register('csrf-refresh', CsrfRefreshController)
})

// Application.stop() does not disconnect the controllers: removing the element
// first lets Stimulus call disconnect() and remove the listeners.
afterEach(async () => {
    document.body.innerHTML = ''
    await Promise.resolve()
    app.stop()
    vi.unstubAllGlobals()
    vi.useRealTimers()
    vi.mocked(visit).mockReset()
})

describe('csrf_refresh_controller', () => {
    it('periodically replaces the tokens of the page with fresh ones', async () => {
        vi.useFakeTimers({ toFake: ['setInterval', 'clearInterval'] })
        const deleteInput = addTokenInput('delete-game')
        const otherDeleteInput = addTokenInput('delete-game')
        const slugInput = addTokenInput('regenerate-slug')
        mockResponse({ 'delete-game': 'fresh-delete', 'regenerate-slug': 'fresh-slug' })
        await connectController(1000)

        vi.advanceTimersByTime(1000)
        await flush()

        expect(fetchMock).toHaveBeenCalledOnce()
        expect(requestedIds()).toEqual(['delete-game', 'regenerate-slug'])
        expect(deleteInput.value).toBe('fresh-delete')
        expect(otherDeleteInput.value).toBe('fresh-delete')
        expect(slugInput.value).toBe('fresh-slug')
    })

    it('pings without token to keep the session alive', async () => {
        await connectController()

        window.dispatchEvent(new Event('online'))
        await flush()

        expect(fetchMock).toHaveBeenCalledOnce()
        expect(requestedIds()).toEqual([])
    })

    it('refreshes when the page becomes visible again', async () => {
        addTokenInput('delete-team')
        await connectController()

        vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('visible')
        document.dispatchEvent(new Event('visibilitychange'))
        await flush()

        expect(requestedIds()).toEqual(['delete-team'])
    })

    it('refreshes after a sleep', async () => {
        vi.useFakeTimers({ toFake: ['Date', 'performance', 'requestAnimationFrame', 'cancelAnimationFrame'] })
        await connectController()

        // Only the wall clock moves, as performance.now() does during a sleep.
        vi.setSystemTime(Date.now() + 60_000)
        await vi.advanceTimersByTimeAsync(20)

        expect(fetchMock).toHaveBeenCalled()
    })

    it('keeps the tokens when the session is already lost', async () => {
        const input = addTokenInput('delete-team')
        mockResponse({ 'delete-team': 'login-page' }, { redirected: true })
        await connectController()

        window.dispatchEvent(new Event('online'))
        await flush()

        expect(input.value).toBe('old')
    })

    it('keeps the tokens when the network is down', async () => {
        const input = addTokenInput('delete-team')
        fetchMock.mockRejectedValue(new TypeError('Failed to fetch'))
        await connectController()

        window.dispatchEvent(new Event('online'))
        await flush()

        expect(input.value).toBe('old')
    })

    it('reports the seconds elapsed since the page was loaded', async () => {
        vi.useFakeTimers({ toFake: ['Date'] })
        await connectController()

        vi.setSystemTime(Date.now() + 90_500)
        await ping()

        expect(reportedIdle()).toBe('90')
    })

    it.each(['pointerdown', 'keydown', 'wheel', 'touchstart'])('reports the seconds elapsed since the last %s', async (type) => {
        vi.useFakeTimers({ toFake: ['Date'] })
        await connectController()

        vi.setSystemTime(Date.now() + 120_000)
        document.body.dispatchEvent(new Event(type, { bubbles: true }))
        vi.setSystemTime(Date.now() + 5_000)
        await ping()

        expect(reportedIdle()).toBe('5')
    })

    it('never reports a negative idle time when the clock goes back', async () => {
        vi.useFakeTimers({ toFake: ['Date'] })
        await connectController()

        vi.setSystemTime(Date.now() - 60_000)
        await ping()

        expect(reportedIdle()).toBe('0')
    })

    it('pings at once on the first interaction after a pause longer than the interval', async () => {
        vi.useFakeTimers({ toFake: ['Date'] })
        await connectController(60_000)

        vi.setSystemTime(Date.now() + 30_000)
        document.dispatchEvent(new Event('keydown'))
        await flush()
        expect(fetchMock).not.toHaveBeenCalled()

        vi.setSystemTime(Date.now() + 60_000)
        document.dispatchEvent(new Event('pointerdown'))
        await flush()
        expect(fetchMock).toHaveBeenCalledOnce()
        expect(reportedIdle()).toBe('0')
    })

    it('goes to the login page when logged out for inactivity', async () => {
        const input = addTokenInput('delete-team')
        mockResponse({ error: 'session_expired' }, { status: 401 })
        await connectController()

        await ping()

        expect(visit).toHaveBeenCalledWith('/user/login')
        expect(input.value).toBe('old')
    })

    it('stays on the page for other errors', async () => {
        mockResponse({}, { status: 500 })
        await connectController()

        await ping()

        expect(visit).not.toHaveBeenCalled()
    })

    it('stops watching interactions once disconnected', async () => {
        vi.useFakeTimers({ toFake: ['Date'] })
        await connectController(60_000)
        element.remove()
        await Promise.resolve()

        vi.setSystemTime(Date.now() + 120_000)
        document.dispatchEvent(new Event('pointerdown'))
        await flush()

        expect(fetchMock).not.toHaveBeenCalled()
    })

    it('stops refreshing once disconnected', async () => {
        await connectController()
        element.remove()
        await Promise.resolve()

        window.dispatchEvent(new Event('online'))
        await flush()

        expect(fetchMock).not.toHaveBeenCalled()
    })
})
