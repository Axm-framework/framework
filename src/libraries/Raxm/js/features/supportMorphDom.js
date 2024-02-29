import { morph } from '../morph.js'
import { on } from '../events.js'

on('effects', (component, html) => {
    if (! html) return

    // Doing this so all the state of components in a nested tree has a chance
    // to update on synthetic's end. (mergeSnapshots kinda deal).
    queueMicrotask(() => {
        morph(component, html)
    })
})
