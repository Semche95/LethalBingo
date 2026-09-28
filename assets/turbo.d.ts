// @hotwired/turbo 8 ships no type declarations: declare the subset used here.
declare module '@hotwired/turbo' {
    export interface VisitOptions {
        action?: 'advance' | 'replace' | 'restore'
        frame?: string
    }

    export const config: {
        drive: {
            progressBarDelay: number
        }
    }

    export function visit(location: string | URL, options?: VisitOptions): void
}
