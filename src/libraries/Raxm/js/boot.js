import store from './store.js'
import DOM from './dom/dom.js'
import { dispatch } from './util/utils.js'
import Component from './component.js'
import Connection from './connection/request.js'

export function start() {
    
    dispatch(document, 'raxm:init')
    dispatch(document, 'raxm:initializing')   

        DOM.rootComponentElementsWithNoParents().forEach(el => {
            store.addComponent(new Component(el, new Connection()))
        })

        dispatch('raxm:load')

        document.addEventListener(
            'visibilitychange',
            () => {
                store.raxmIsInBackground = document.hidden
            },
            false
        )

        store.initialRenderIsFinished = true

    setTimeout(() => window.Raxm.initialRenderIsFinished = true)

    dispatch(document, 'raxm:initialized')
}

export function stop() {
    // @todo...
}

export function rescan() {
    // @todo...
}
