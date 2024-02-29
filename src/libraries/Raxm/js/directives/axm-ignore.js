import { directive } from '../directives.js'

directive('ignore', ({ el, directive }) => {
    if (directive.modifiers.includes('self')) {
        el.__raxm_ignore_self = true
    } else {
        el.__raxm_ignore = true
    }
})
