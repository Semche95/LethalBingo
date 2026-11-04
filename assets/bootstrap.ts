import { config as turboConfig } from '@hotwired/turbo'
import { startStimulusApp, registerControllers } from 'vite-plugin-symfony/stimulus/helpers'
import AutocompleteController from '../vendor/symfony/ux-autocomplete/assets/dist/controller.js'
import type { Component } from '@symfony/ux-live-component'
import { restoreHiddenNonces } from './turbo_nonce'
import { hideTooltips, syncTooltips } from './utils'

turboConfig.drive.progressBarDelay = 10

// Only the dev profiler toolbar uses nonced scripts; the prod build drops this.
// The listener lives as long as the document, so it is never removed.
if (import.meta.env.DEV) {
    document.addEventListener('turbo:before-render', (event) => {
        restoreHiddenNonces((event as CustomEvent<{ newBody: HTMLElement }>).detail.newBody)
    })
}

// A re-render replaces some elements of a Live Component, while Bootstrap shows
// the tooltip at the end of the page: the tooltip of a removed button (pause,
// resume...) would stay on screen and the new buttons would have none. The
// tooltips are hidden first, the one of a clicked button staying otherwise as
// long as it keeps the focus.
// Listened before Stimulus starts, so that no component connects unseen; the
// listener lives as long as the document, so it is never removed.
const tooltipComponents = new WeakSet<Component>()
document.addEventListener('live:connect', (event) => {
    const component = (event as CustomEvent<{ component: Component }>).detail.component
    if (tooltipComponents.has(component)) {
        return
    }
    tooltipComponents.add(component)

    component.on('render:started', () => hideTooltips(component.element))
    component.on('render:finished', () => syncTooltips(component.element))
})

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
