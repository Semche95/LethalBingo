// Threshold alerts for the game timer (docs/TIMER.md section 5):
//  - colour: pure function of the current remaining time, no state;
//  - sound: one-shot on a downward crossing, detected against the previous
//    remaining time, which is the only state kept between evaluations.

// Warning at every 10-minute multiple of remaining time, except the full duration.
const WARNING_STEP_SECONDS = 600

// Danger thresholds: 5, 3 and 1 minutes.
const DANGER_BOUNDARIES_SECONDS = [300, 180, 60]

// How long the colour stays on after crossing a threshold.
const COLOR_WINDOW_SECONDS = 5

export type ThresholdAlert = 'warning' | 'danger' | 'end' | null
export type ThresholdColor = 'warning' | 'danger' | null

export function computeAlertColor(remainingSeconds: number, durationSeconds: number): ThresholdColor {
    // Continuous danger during the last minute, including zero.
    if (remainingSeconds < 60) {
        return 'danger'
    }

    if (remainingSeconds < 300 && remainingSeconds >= 300 - COLOR_WINDOW_SECONDS) {
        return 'danger'
    }
    if (remainingSeconds < 180 && remainingSeconds >= 180 - COLOR_WINDOW_SECONDS) {
        return 'danger'
    }

    const remainder = remainingSeconds % WARNING_STEP_SECONDS
    if (remainder >= WARNING_STEP_SECONDS - COLOR_WINDOW_SECONDS) {
        const aboveMultiple = remainingSeconds - remainder + WARNING_STEP_SECONDS
        if (aboveMultiple < durationSeconds) {
            return 'warning'
        }
    }

    return null
}

export interface ThresholdState {
    previousRemainingSeconds: number | null
}

export function createThresholdState(): ThresholdState {
    return {
        previousRemainingSeconds: null,
    }
}

/**
 * Returns the alert for a downward threshold crossing since the previous call.
 * The reference is resynced on every call; canTriggerSound=false (manual
 * referee adjustments) keeps it up to date while staying silent.
 */
export function evaluateThresholds(
    remainingSeconds: number,
    durationSeconds: number,
    state: ThresholdState,
    canTriggerSound: boolean = true,
): ThresholdAlert {
    let alert: ThresholdAlert = null
    const prev = state.previousRemainingSeconds

    if (prev !== null && remainingSeconds < prev) {
        if (prev > 0 && remainingSeconds <= 0) {
            alert = 'end'
        } else if (DANGER_BOUNDARIES_SECONDS.some(boundary => prev >= boundary && remainingSeconds < boundary)) {
            alert = 'danger'
        } else {
            const prevFloor = Math.floor(prev / WARNING_STEP_SECONDS)
            const currentFloor = Math.floor(remainingSeconds / WARNING_STEP_SECONDS)

            if (prevFloor > currentFloor && (currentFloor + 1) < durationSeconds / WARNING_STEP_SECONDS) {
                alert = 'warning'
            }
        }
    }

    state.previousRemainingSeconds = remainingSeconds

    return canTriggerSound ? alert : null
}

export interface UpcomingAlert {
    alert: Exclude<ThresholdAlert, null>
    inSeconds: number
}

/**
 * Lists the alerts evaluateThresholds() will raise if the countdown runs on
 * uninterrupted, with their delay from now. Each one fires when the displayed
 * (rounded up) time drops below its threshold, i.e. when the exact remaining
 * time reaches threshold - 1, or zero for the end.
 */
export function listUpcomingAlerts(exactRemainingSeconds: number, durationSeconds: number): UpcomingAlert[] {
    const warnings: UpcomingAlert[] = []
    for (let threshold = WARNING_STEP_SECONDS; threshold < durationSeconds; threshold += WARNING_STEP_SECONDS) {
        warnings.push({ alert: 'warning', inSeconds: exactRemainingSeconds - (threshold - 1) })
    }

    const dangers: UpcomingAlert[] = DANGER_BOUNDARIES_SECONDS.map(threshold => ({
        alert: 'danger',
        inSeconds: exactRemainingSeconds - (threshold - 1),
    }))

    const end: UpcomingAlert = { alert: 'end', inSeconds: exactRemainingSeconds }

    return [...warnings, ...dangers, end]
        .filter(({ inSeconds }) => inSeconds > 0)
        .sort((a, b) => a.inSeconds - b.inSeconds)
}
