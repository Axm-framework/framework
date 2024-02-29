import { walk } from '../util/utils.js'
import store from '../store.js'
import { PREFIX_REGEX, PREFIX_STRING} from '../directives.js';

export default function () {
    window.addEventListener('axm:load', () => {
        if (!window.Alpine) return

        refreshAlpineAfterEveryRaxmRequest()

        addDollarSignRaxm()

        supportEntangle()
    })
}

function refreshAlpineAfterEveryRaxmRequest() {
    if (isV3()) {
        store.registerHook('message.processed', (message, axmComponent) => {
            walk(axmComponent.el, el => {
                if (el._x_hidePromise) return
                if (el._x_runEffects) el._x_runEffects()
            })
        })

        return
    }

    if (!window.Alpine.onComponentInitialized) return

    window.Alpine.onComponentInitialized(component => {
        let axmEl = component.$el.closest(`[${PREFIX_REGEX}id]`)
        
        if (axmEl && axmEl.__axm) {
            store.registerHook('message.processed', (message, axmComponent) => {
                if (axmComponent === axmEl.__axm) {
                    component.updateElements(component.$el)
                }
            })
        }
    })
}

function addDollarSignRaxm() {
    if (isV3()) {
        window.Alpine.magic(`${PREFIX_STRING}`, function (el) {
            let axmEl = el.closest(`[${PREFIX_REGEX}id]`)

            if (!axmEl)
                console.warn(
                    'Alpine: Cannot reference "$raxm" outside a Raxm component.'
                )

            let component = axmEl.__axm

            return component.$raxm
        })
        return
    }

    if (!window.Alpine.addMagicProperty) return

    window.Alpine.addMagicProperty(`${PREFIX_STRING}`, function (componentEl) {
        let axmEl = componentEl.closest(`[${PREFIX_REGEX}id]`)

        if (!axmEl)
            console.warn(
                'Alpine: Cannot reference "$raxm" outside a Raxm component.'
            )

        let component = axmEl.__axm

        return component.$raxm
    })
}

function supportEntangle() {
    if (isV3()) return

    if (!window.Alpine.onBeforeComponentInitialized) return

    window.Alpine.onBeforeComponentInitialized(component => {
        let axmEl = component.$el.closest(`[${PREFIX_REGEX}id]`)

        if (axmEl && axmEl.__axm) {
            Object.entries(component.unobservedData).forEach(
                ([key, value]) => {
                    if (
                        !!value &&
                        typeof value === 'object' &&
                        value.axmEntangle
                    ) {
                        // Ok, it looks like someone set an Alpine property to $raxm.entangle or @entangle.
                        let axmProperty = value.axmEntangle
                        let isDeferred = value.isDeferred
                        let axmComponent = axmEl.__axm

                        let axmPropertyValue = axmEl.__axm.get(axmProperty)

                        // Check to see if the Raxm property exists and if not log a console error
                        // and return so everything else keeps running.
                        if (typeof axmPropertyValue === 'undefined') {
                            console.error(`Raxm Entangle Error: Raxm property '${axmProperty}' cannot be found`)
                            return
                        }

                        // Let's set the initial value of the Alpine prop to the Raxm prop's value.
                        component.unobservedData[key]
                            // We need to stringify and parse it though to get a deep clone.
                            = JSON.parse(JSON.stringify(axmPropertyValue))

                        let blockAlpineWatcher = false

                        // Now, we'll watch for changes to the Alpine prop, and fire the update to Raxm.
                        component.unobservedData.$watch(key, value => {
                            // Let's also make sure that this watcher isn't a result of a Raxm response.
                            // If it is, we don't need to "re-update" Raxm. (sending an extra useless) request.
                            if (blockAlpineWatcher === true) {
                                blockAlpineWatcher = false
                                return
                            }

                            // If the Alpine value is the same as the Raxm value, we'll skip the update for 2 reasons:
                            // - It's just more efficient, why send needless requests.
                            // - This prevents a circular dependancy with the other watcher below.
                            // - Due to the deep clone using stringify, we need to do the same here to compare.
                            if (
                                JSON.stringify(value) ==
                                JSON.stringify(
                                    axmEl.__axm.getPropertyValueIncludingDefers(
                                        axmProperty
                                    )
                                )
                            ) return

                            // We'll tell Raxm to update the property, but we'll also tell Raxm
                            // to not call the normal property watchers on the way back to prevent another
                            // circular dependancy.
                            axmComponent.set(
                                axmProperty,
                                value,
                                isDeferred,
                                // Block firing of Raxm watchers for this data key when the request comes back.
                                // Unless it is deferred, in which cause we don't know if the state will be the same, so let it run.
                                isDeferred ? false : true
                            )
                        })

                        // We'll also listen for changes to the Raxm prop, and set them in Alpine.
                        axmComponent.watch(
                            axmProperty,
                            value => {
                                // Ensure data is deep cloned otherwise Alpine mutates Raxm data
                                component.$data[key] = typeof value !== 'undefined' ? JSON.parse(JSON.stringify(value)) : value
                            }
                        )
                    }
                }
            )
        }
    })
}

export function getEntangleFunction(component) {
    if (isV3()) {
        return (name, defer = false) => {
            let isDeferred = defer
            let axmProperty = name
            let axmComponent = component
            let axmPropertyValue = component.get(axmProperty)

            let interceptor = Alpine.interceptor((initialValue, getter, setter, path, key) => {
                // Check to see if the Raxm property exists and if not log a console error
                // and return so everything else keeps running.
                if (typeof axmPropertyValue === 'undefined') {
                    console.error(`Raxm Entangle Error: Raxm property '${axmProperty}' cannot be found`)
                    return
                }

                // Let's set the initial value of the Alpine prop to the Raxm prop's value.
                let value
                    // We need to stringify and parse it though to get a deep clone.
                    = JSON.parse(JSON.stringify(axmPropertyValue))

                setter(value)

                // Now, we'll watch for changes to the Alpine prop, and fire the update to Raxm.
                window.Alpine.effect(() => {
                    let value = getter()

                    if (
                        JSON.stringify(value) ==
                        JSON.stringify(
                            axmComponent.getPropertyValueIncludingDefers(
                                axmProperty
                            )
                        )
                    ) return

                    // We'll tell Raxm to update the property, but we'll also tell Raxm
                    // to not call the normal property watchers on the way back to prevent another
                    // circular dependancy.
                    axmComponent.set(
                        axmProperty,
                        value,
                        isDeferred,
                        // Block firing of Raxm watchers for this data key when the request comes back.
                        // Unless it is deferred, in which cause we don't know if the state will be the same, so let it run.
                        isDeferred ? false : true
                    )
                })

                // We'll also listen for changes to the Raxm prop, and set them in Alpine.
                axmComponent.watch(
                    axmProperty,
                    value => {
                        // Ensure data is deep cloned otherwise Alpine mutates Raxm data
                        window.Alpine.disableEffectScheduling(() => {
                            setter(typeof value !== 'undefined' ? JSON.parse(JSON.stringify(value)) : value)
                        })
                    }
                )

                return value
            }, obj => {
                Object.defineProperty(obj, 'defer', {
                    get() {
                        isDeferred = true

                        return obj
                    }
                })
            })

            return interceptor(axmPropertyValue)
        }
    }

    return (name, defer = false) => ({
        isDeferred: defer,
        axmEntangle: name,
        get defer() {
            this.isDeferred = true
            return this
        },
    })
}

export function alpinifyElementsForMorphdom(from, to) {
    if (isV3()) {
        return alpinifyElementsForMorphdomV3(from, to)
    }

    // If the element we are updating is an Alpine component...
    if (from.__x) {
        // Then temporarily clone it (with it's data) to the "to" element.
        // This should simulate backend Raxm being aware of Alpine changes.
        window.Alpine.clone(from.__x, to)
    }

    // x-show elements require care because of transitions.
    if (
        Array.from(from.attributes)
            .map(attr => attr.name)
            .some(name => /x-show/.test(name))
    ) {
        if (from.__x_transition) {
            // This covers @entangle('something')
            from.skipElUpdatingButStillUpdateChildren = true
        } else {
            // This covers x-show="$raxm.something"
            //
            // If the element has x-show, we need to "reverse" the damage done by "clone",
            // so that if/when the element has a transition on it, it will occur naturally.
            if (isHiding(from, to)) {
                let style = to.getAttribute('style')

                if (style) {
                    to.setAttribute('style', style.replace('display: none;', ''))
                }
            } else if (isShowing(from, to)) {
                to.style.display = from.style.display
            }
        }
    }
}

function alpinifyElementsForMorphdomV3(from, to) {
    if (from.nodeType !== 1) return

    // If the element we are updating is an Alpine component...
    if (from._x_dataStack) {
        // Then temporarily clone it (with it's data) to the "to" element.
        // This should simulate backend Raxm being aware of Alpine changes.
        window.Alpine.clone(from, to)
    }
}

function isHiding(from, to) {
    if (beforeAlpineTwoPointSevenPointThree()) {
        return from.style.display === '' && to.style.display === 'none'
    }

    return from.__x_is_shown && !to.__x_is_shown
}

function isShowing(from, to) {
    if (beforeAlpineTwoPointSevenPointThree()) {
        return from.style.display === 'none' && to.style.display === ''
    }

    return !from.__x_is_shown && to.__x_is_shown
}

function beforeAlpineTwoPointSevenPointThree() {
    let [major, minor, patch] = window.Alpine.version.split('.').map(i => Number(i))

    return major <= 2 && minor <= 7 && patch <= 2
}

function isV3() {
    return window.Alpine && window.Alpine.version && /^3\..+\..+$/.test(window.Alpine.version)
}
