import { config as turboConfig } from '@hotwired/turbo'
import { startStimulusApp, registerControllers } from 'vite-plugin-symfony/stimulus/helpers'
import AutocompleteController from '../vendor/symfony/ux-autocomplete/assets/dist/controller.js'
import { restoreHiddenNonces } from './turbo_nonce'

turboConfig.drive.progressBarDelay = 10

// Only the dev profiler toolbar uses nonced scripts; the prod build drops this.
// The listener lives as long as the document, so it is never removed.
if (import.meta.env.DEV) {
    document.addEventListener('turbo:before-render', (event) => {
        restoreHiddenNonces((event as CustomEvent<{ newBody: HTMLElement }>).detail.newBody)
    })
}

const app = startStimulusApp()
registerControllers(
    app,
    import.meta.glob<StimulusControllerInfosImport>(
        './controllers/**/*_controller.ts',
        {
            query: '?stimulus',
            /**
             * always true, the `lazy` behavior is managed internally with
             * import.meta.stimulusFetch (see reference)
             */
            eager: true,
        },
    ),
)
app.register('symfony--ux-autocomplete--autocomplete', AutocompleteController)
