import { Controller } from '@hotwired/stimulus'
import { watchClientClockJumps } from './game/game_timer_anchor'

// PHP drops a session left without request for 24 minutes (session.gc_maxlifetime),
// taking the CSRF tokens stored in it along: ping well before that.
const DEFAULT_INTERVAL = 10 * 60 * 1000

/**
 * Keeps the session alive on pages left open for a long time (game, referee
 * screens) and replaces the CSRF tokens rendered by the CsrfInput component
 * with fresh ones, so a form submitted hours later is not rejected.
 */
export default class extends Controller<HTMLElement> {
    static override values = {
        url: String,
        interval: { type: Number, default: DEFAULT_INTERVAL },
    }

    declare readonly urlValue: string
    declare readonly intervalValue: number

    private intervalId: number | null = null
    private stopClockJumpWatch: (() => void) | null = null

    override connect(): void {
        this.intervalId = window.setInterval(this.refresh, this.intervalValue)
        // Timers are suspended while the computer sleeps: refresh on wake up.
        this.stopClockJumpWatch = watchClientClockJumps(this.refresh)
        document.addEventListener('visibilitychange', this.onVisibilityChange)
        window.addEventListener('online', this.refresh)
    }

    override disconnect(): void {
        if (this.intervalId !== null) {
            window.clearInterval(this.intervalId)
            this.intervalId = null
        }
        this.stopClockJumpWatch?.()
        this.stopClockJumpWatch = null
        document.removeEventListener('visibilitychange', this.onVisibilityChange)
        window.removeEventListener('online', this.refresh)
    }

    private readonly onVisibilityChange = (): void => {
        if (document.visibilityState === 'visible') {
            this.refresh()
        }
    }

    readonly refresh = async (): Promise<void> => {
        const inputs = document.querySelectorAll<HTMLInputElement>('input[data-csrf-token-id]')
        const url = new URL(this.urlValue, window.location.origin)
        new Set(Array.from(inputs, (input) => input.dataset.csrfTokenId ?? ''))
            .forEach((id) => url.searchParams.append('ids[]', id))

        let tokens: Record<string, string>
        try {
            const response = await fetch(url, { cache: 'no-store', headers: { Accept: 'application/json' } })
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
