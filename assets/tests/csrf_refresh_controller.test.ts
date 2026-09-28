import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { Application } from '@hotwired/stimulus'
import CsrfRefreshController from '../controllers/csrf_refresh_controller'

let app: Application
let element: HTMLDivElement
let fetchMock: ReturnType<typeof vi.fn>

function mockResponse(tokens: Record<string, string>, init: { ok?: boolean, redirected?: boolean } = {}) {
    fetchMock.mockResolvedValue({ ok: init.ok ?? true, redirected: init.redirected ?? false, json: async () => tokens })
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

    it('stops refreshing once disconnected', async () => {
        await connectController()
        element.remove()
        await Promise.resolve()

        window.dispatchEvent(new Event('online'))
        await flush()

        expect(fetchMock).not.toHaveBeenCalled()
    })
})
