import '@hotwired/turbo'
import { startStimulusApp, registerControllers } from 'vite-plugin-symfony/stimulus/helpers'
import AutocompleteController from '../vendor/symfony/ux-autocomplete/assets/dist/controller.js'

Turbo.setProgressBarDelay(10)

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
