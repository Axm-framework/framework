import { toggleBooleanStateDirective } from './shared.js'
import { directive, getDirectives } from '../directives.js'
import DOM from '../dom/dom.js'
import { dataGet, WeakBag } from '../util/utils.js'
import { on } from '../events.js'
import store from '../store.js'

let refreshDirtyStatesByComponent = new WeakBag

store.registerHook('component.initialized', (directive, el, component) => {

// on('commit', ({ component, respond }) => {
    // respond(() => {
        setTimeout(() => { // Doing a "setTimeout" to let morphdom do its thing first...
            refreshDirtyStatesByComponent.each(component, i => i(false))
        })
    // })
})


directive('dirty', ({ el, directive, component }) => {
    let targets = dirtyTargets(el)

    let oldIsDirty = false

    let initialDisplay = el.style.display
    
    let refreshDirtyState = (isDirty) => {
        toggleBooleanStateDirective(el, directive, isDirty, initialDisplay)

        oldIsDirty = isDirty
    }

    refreshDirtyStatesByComponent.add(component, refreshDirtyState)

    let isDirty = false

    for (let i = 0; i < targets.length; i++) {
        if (isDirty) break;

        let target = targets[i]

        isDirty = DOM.valueFromInput(el, component) != component.get(target)
    }

    if (oldIsDirty !== isDirty) {
        refreshDirtyState(isDirty)
    }

    oldIsDirty = isDirty
})



function dirtyTargets(el) {
    let directives = getDirectives(el)
    let targets = []

    if (directives.has('model')) {
        targets.push(directives.get('model').expression)
    }

    if (directives.has('target')) {
        targets = targets.concat(
            directives
            .get('target')
            .expression.split(',')
            .map(s => s.trim())
        )
    }

    return targets
}
