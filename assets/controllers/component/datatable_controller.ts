import { Controller } from '@hotwired/stimulus'
import { visit } from '@hotwired/turbo'

/* stimulusFetch: 'lazy' */
export default class extends Controller<HTMLElement> {
    private boundMakeRowsClickable!: () => void;

    override initialize() {
        // Bind the method once to maintain the same reference for adding/removing
        this.boundMakeRowsClickable = this.makeRowsClickable.bind(this);
    }

    override connect() {
        // Make rows clickable initially
        this.makeRowsClickable();

        // For Turbo or other dynamic content loading
        document.addEventListener('turbo:load', this.boundMakeRowsClickable);
        document.addEventListener('turbo:frame-load', this.boundMakeRowsClickable);
    }

    override disconnect() {
        // Remove event listeners to prevent memory leaks
        document.removeEventListener('turbo:load', this.boundMakeRowsClickable);
        document.removeEventListener('turbo:frame-load', this.boundMakeRowsClickable);
    }

    private makeRowsClickable(): void {
        document.querySelectorAll('.datatable tbody tr').forEach((row) => {
            const link = row.querySelector('a[href]');
            if (!link) {
                return;
            }

            row.classList.add('cursor-pointer');
            row.addEventListener('click', (e) => {
                if (!(e.target instanceof HTMLElement) || e.target.tagName === 'A' || e.target.parentElement?.tagName === 'A') {
                    return;
                }

                const url = (link as HTMLAnchorElement).getAttribute('href') || '';
                visit(url);
            });
        });
    }
}
