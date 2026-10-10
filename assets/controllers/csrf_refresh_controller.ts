import { Controller } from '@hotwired/stimulus'
import { visit } from '@hotwired/turbo'
import { watchWakeUps } from '../wake_up'

// PHP drops a session left without request for 24 minutes (session.gc_maxlifetime),
// taking the CSRF tokens stored in it along: ping well before that.
const DEFAULT_INTERVAL = 10 * 60 * 1000

// Interactions that prove someone is using the page (SessionIdleTimeoutListener).
const INTERACTION_EVENTS = ['pointerdown', 'keydown', 'wheel', 'touchstart'] as const
const INTERACTION_OPTIONS: AddEventListenerOptions = { capture: true, passive: true }

/**
 * Keeps the session alive on pages left open for a long time (game, referee
 * screens) and replaces the CSRF tokens rendered by the CsrfInput component
 * with fresh ones, so a form submitted hours later is not rejected.
 *
 * Each ping also reports how long the page has gone without a click or key
 * press ("idle", in seconds): the server logs the user out after hours without
 * activity and answers 401, then the page goes to the login page. Clicks that
 * only trigger Live Component actions are known to the server this way, up to
 * one ping interval late, which does not matter against a timeout of hours.
 */
export default class extends Controller<HTMLElement> {
    static override values = {
        url: String,
        interval: { type: Number, default: DEFAULT_INTERVAL },
        loginUrl: String,
    }

    declare readonly urlValue: string
    declare readonly intervalValue: number
    declare readonly loginUrlValue: string

    // Wall clock (Date.now): the time spent asleep counts as idle time.
    private lastInteraction = Date.now()

    private intervalId: number | null = null
    private stopWakeUpWatch: (() => void) | null = null

    override connect(): void {
        // Connected by a page load or a Turbo visit, both activity.
        this.lastInteraction = Date.now()
        INTERACTION_EVENTS.forEach((type) => document.addEventListener(type, this.onInteraction, INTERACTION_OPTIONS))
        this.intervalId = window.setInterval(this.refresh, this.intervalValue)
        // Timers are suspended while the computer sleeps: refresh on wake up.
        this.stopWakeUpWatch = watchWakeUps(this.refresh, { visibility: true })
    }

    override disconnect(): void {
        if (this.intervalId !== null) {
            window.clearInterval(this.intervalId)
            this.intervalId = null
        }
        this.stopWakeUpWatch?.()
        this.stopWakeUpWatch = null
        INTERACTION_EVENTS.forEach((type) => document.removeEventListener(type, this.onInteraction, INTERACTION_OPTIONS))
    }

    private readonly onInteraction = (): void => {
        const now = Date.now()
        const wasIdle = now - this.lastInteraction >= this.intervalValue
        this.lastInteraction = now
        // Back after a long pause: tell the server now rather than at the next
        // ping, in case this click's Live request comes close to the timeout.
        if (wasIdle) {
            this.refresh()
        }
    }

    readonly refresh = async (): Promise<void> => {
        const inputs = document.querySelectorAll<HTMLInputElement>('input[data-csrf-token-id]')
        const url = new URL(this.urlValue, window.location.origin)
        new Set(Array.from(inputs, (input) => input.dataset.csrfTokenId ?? ''))
            .forEach((id) => url.searchParams.append('ids[]', id))
        url.searchParams.set('idle', String(Math.max(0, Math.floor((Date.now() - this.lastInteraction) / 1000))))

        let tokens: Record<string, string>
        try {
            const response = await fetch(url, { cache: 'no-store', headers: { Accept: 'application/json' } })
            // Logged out after a long inactivity: show the login page, not stale content.
            if (response.status === 401) {
                visit(this.loginUrlValue)
                return
            }
            // Session already lost: the user was redirected to the login page.
            if (!response.ok || response.redirected) {
                return
            }
            tokens = await response.json()
        } catch {
            // Network down: the next ping or the 'online' event will retry.
            return
        }

        inputs.forEach((input) => {
            const token = tokens[input.dataset.csrfTokenId ?? '']
            if (token) {
                input.value = token
            }
        })
    }
}
