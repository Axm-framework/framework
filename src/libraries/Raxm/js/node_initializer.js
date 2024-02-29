import { kebabCase, debounce, call } from './util/utils.js'
import { getDirectives } from './directives.js';
// import MethodAction from './action/method.js'
import { addMethodAction, callAfterModelDebounce, addPrefetchAction } from './commit.js'

import store from './store.js'

export default {

    initialize(el, component) {
        getDirectives(el).all().forEach(directive => {
            store.callHook('directive.initialized', { el, component, directive, cleanup: () => {}})
            this.attachDomListener(el, directive, component)
        })

        store.callHook('element.initialized', el, component)
    },

    attachDomListener(el, directive, component) {
        switch (directive.type) {
            case 'keydown':
            case 'keyup':
                this.attachListener(el, directive, component, e => {
                    // Detect system modifier key combinations if specified.
                    const systemKeyModifiers = [
                        'ctrl',
                        'shift',
                        'alt',
                        'meta',
                        'cmd',
                        'super',
                    ]
                    const selectedSystemKeyModifiers = systemKeyModifiers.filter(
                        key => directive.modifiers.includes(key)
                    )

                    if (selectedSystemKeyModifiers.length > 0) {
                        const selectedButNotPressedKeyModifiers = selectedSystemKeyModifiers.filter(
                            key => {
                                // Alias "cmd" and "super" to "meta"
                                if (key === 'cmd' || key === 'super')
                                    key = 'meta'

                                return !e[`${key}Key`]
                            }
                        )

                        if (selectedButNotPressedKeyModifiers.length > 0)
                            return false
                    }

                    // Handle spacebar
                    if (e.keyCode === 32 || (e.key === ' ' || e.key === 'Spacebar')) {
                        return directive.modifiers.includes('space')
                    }

                    // Strip 'debounce' modifier and time modifiers from modifiers list
                    let modifiers = directive.modifiers.filter(modifier => {
                        return (
                            !modifier.match(/^debounce$/) &&
                            !modifier.match(/^[0-9]+m?s$/)
                        )
                    })

                    // Only handle listener if no, or matching key modifiers are passed.
                    // It's important to check that e.key exists - OnePassword's extension does weird things.
                    return Boolean(modifiers.length === 0 || (e.key && modifiers.includes(kebabCase(e.key))))
                })
                break
            case 'click':
                this.attachListener(el, directive, component, e => {
                    // We only care about elements that have the .self modifier on them.
                    if (!directive.modifiers.includes('self')) return

                    // This ensures a listener is only run if the event originated
                    // on the elemenet that registered it (not children).
                    // This is useful for things like modal back-drop listeners.
                    return el.isSameNode(e.target)
                })
                break
            default:
                this.attachListener(el, directive, component)
                break
        }
    },

    attachListener(el, directive, component, callback) {

        if (directive.modifiers.includes('prefetch')) {
            el.addEventListener('mouseenter', () => {
                addPrefetchAction(component, directive.method, directive.params)
            })
        }

        const event = directive.type
        const handler = e => {
            if (callback && callback(e) === false) {
                return
            }

            //mio
            if (directive.modifiers.includes('front')) {
                const { method, params } = directive
                   return call(method, params)
            }

            callAfterModelDebounce(() => {
                const el = e.target

                directive.setEventContext(e)

                // This is outside the conditional below so "axm:click.prevent" without
                // a value still prevents default.
                this.preventAndStop(e, directive.modifiers)
                const method = directive.method
                let params = directive.params

                if (
                    params.length === 0 &&
                    e instanceof CustomEvent &&
                    e.detail
                ) {
                    params.push(e.detail)
                }

                // Check for global event emission.
                if (method === '$emit') {
                    component.scopedListeners.call(...params)
                    store.emit(...params)
                    return
                }

                if (method === '$emitUp') {
                    store.emitUp(el, ...params)
                    return
                }

                if (method === '$emitSelf') {
                    store.emitSelf(component.id, ...params)
                    return
                }

                if (method === '$emitTo') {
                    store.emitTo(...params)
                    return
                }

                if (directive.value) {
                    // component.addAction(new MethodAction(method, params, el))
                    addMethodAction(component, method, params)
                }
            })
        }

        const debounceIf = (condition, callback, time) => {
            return condition ? debounce(callback, time) : callback
        }

        const hasDebounceModifier = directive.modifiers.includes('debounce')
        const debouncedHandler = debounceIf(
            hasDebounceModifier,
            handler,
            directive.durationOr(150)
        )

        el.addEventListener(event, debouncedHandler)

        component.addListenerForTeardown(() => {
            el.removeEventListener(event, debouncedHandler)
        })
    },

    preventAndStop(event, modifiers) {
        modifiers.includes('prevent') && event.preventDefault()

        modifiers.includes('stop') && event.stopPropagation()
    }
}
