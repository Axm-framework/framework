import { findComponent } from "../store.js";
import { on } from '../events.js'

on('commit.prepare', ({ component }) => {
    component.children.forEach(child => {
        let childMeta = child.snapshot.memo
        let bindings  = childMeta.bindings

        // If this child has a binding from the parent
        if (bindings) child.$raxm.$refresh()
    })
})

