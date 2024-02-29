// import { findComponent } from "../store.js";
import { on } from '../events.js'
// import Alpine from 'alpinejs'

export function generateEntangleFunction(component) {
    return (name, live) => {
        let isLive = live
        let raxmProperty = name
        let raxmComponent = component.$wire
        let raxmPropertyValue = raxmComponent.get(raxmProperty)

        let interceptor = Alpine.interceptor((initialValue, getter, setter, path, key) => {
            // Check to see if the raxm property exists and if not log a console error
            // and return so everything else keeps running.
            if (typeof raxmPropertyValue === 'undefined') {
                console.error(`Raxm Entangle Error: Raxm property '${raxmProperty}' cannot be found`)
                return
            }

            queueMicrotask(() => {
                Alpine.entangle({
                    // Outer scope...
                    get() {
                        return raxmComponent.get(name)
                    },
                    set(value) {
                        raxmComponent.set(name, value, isLive)
                    }
                }, {
                    // Inner scope...
                    get() {
                        return getter()
                    },
                    set(value) {
                        setter(value)
                    }
                })
            })

            return raxmComponent.get(name)
        }, obj => {
            Object.defineProperty(obj, 'live', {
                get() {
                    isLive = true

                    return obj
                }
            })
        })

        return interceptor(raxmPropertyValue)
    }
}
