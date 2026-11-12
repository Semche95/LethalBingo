import { beforeEach, afterEach, vi } from 'vitest'
import { Application } from '@hotwired/stimulus'
import GameTimerController from '../controllers/game/game_timer_controller'
import { createThresholdState } from '../controllers/game/game_timer_thresholds'
import { installFakeAudioContext } from './fake_audio_context'

// Shared setup of the game_timer_controller test files: each one calls
// useTimerControllerApp() in its describe() and builds its timer with the
// helpers below.

let app: Application

function kebab(name: string): string {
    return name.replace(/[A-Z]/g, (m) => '-' + m.toLowerCase())
}

export interface TimerValues {
    timerMode?: 'countdown' | 'stopwatch'
    countdownDurationSeconds?: number
    gameStartedAt?: number | null
    gameFinishedAt?: number | null
    gameCountdownZeroAt?: number | null
    gamePausedRemainingSeconds?: number
    gameIsPaused?: boolean
    gamePausedAt?: number | null
    gamePausedDurationSeconds?: number
    serverTime?: number
    serverTimeUrl?: string
}

export const SERVER_TIME = 1_700_000_000
export const START_SEC = SERVER_TIME - 1800 // 30 minutes elapsed -> 00:30:00 remaining

// A one-hour countdown by default: a running game started at gameStartedAt
// reaches zero one hour later, unless the test gives its own gameCountdownZeroAt.
// A stopwatch has no countdown zero moment.
export function buildElement(values: TimerValues = {}) {
    const el = document.createElement('div')
    el.setAttribute('data-controller', 'game--game-timer')

    const isStopwatch = values.timerMode === 'stopwatch'
    const isRunning = values.gameStartedAt && !values.gameIsPaused && !isStopwatch
    const withCountdown = {
        timerMode: 'countdown',
        countdownDurationSeconds: 3600,
        gameCountdownZeroAt: isRunning ? values.gameStartedAt! + 3600 : null,
        ...values,
    }

    for (const [key, val] of Object.entries(withCountdown)) {
        if (val === null || val === undefined) continue
        el.setAttribute(`data-game--game-timer-${kebab(key)}-value`, String(val))
    }

    el.innerHTML = `
        <span data-game--game-timer-target="timerDisplay"></span>
        <span data-game--game-timer-target="pauseBadge" class="d-none"></span>
        <button data-game--game-timer-target="pauseButton"></button>
        <button data-game--game-timer-target="soundButton" class="d-none"></button>
    `
    document.body.appendChild(el)
    return el
}

// Records the sounds requested by the controller ('notification' or 'end').
export function spyOnSounds(controller: any) {
    const play = vi.spyOn(controller.sound, 'play')
    return (name: string) => play.mock.calls.filter(([played]) => played === name).length
}

export async function connectController(el: HTMLElement): Promise<any> {
    for (let i = 0; i < 50; i++) {
        const controller = app.getControllerForElementAndIdentifier(el, 'game--game-timer')
        if (controller) return controller
        await Promise.resolve()
    }
    throw new Error('Controller never connected')
}

// The fake performance.now() may return the real epoch on its first read, which
// would skew the client-time estimate. Normalizing the anchor right after connect
// makes the delta start at 0 and keeps the estimate fully deterministic.
//
// connect()'s own initial updateTimerDisplay() runs BEFORE this normalization, so
// with the skewed first read it may spuriously seed the SOUND detection reference
// with a wrong remaining value. That never happens on a real page load (correct
// serverTime + real clock), so we reset the sound threshold state here to the
// clean post-connect slate a real controller would have. Colour needs no reset:
// it is a pure function of the current remaining time, recomputed every frame.
export async function connectWithAnchor(values: TimerValues = {}): Promise<any> {
    const el = buildElement(values)
    const controller = await connectController(el)
    controller.syncedServerTime = values.serverTime ?? SERVER_TIME
    controller.clientTimeAtRender = performance.now()
    controller.thresholdState = createThresholdState()
    controller.timerDisplayTarget.className = ''
    controller.updateTimerDisplay()
    return controller
}

export function useTimerControllerApp(): void {
    beforeEach(async () => {
        vi.useFakeTimers({
            toFake: [
                'setTimeout',
                'setInterval',
                'clearTimeout',
                'clearInterval',
                'Date',
                'performance',
                'requestAnimationFrame',
                'cancelAnimationFrame',
            ],
        })

        installFakeAudioContext()

        app = new Application(document.documentElement)
        app.register('game--game-timer', GameTimerController)
        await app.start()
    })

    afterEach(() => {
        delete (document as any).visibilityState
        app.stop()
        document.body.innerHTML = ''
        vi.runOnlyPendingTimers()
        vi.useRealTimers()
        vi.clearAllMocks()
    })
}
