import { dispatch, dispatchSelf, dispatchTo, listen } from './features/supportEvents.js'
import { generateEntangleFunction } from './features/supportEntangle.js'
import { closestComponent } from './store.js'
import { addMethodAction, set, get } from './commit.js'
import { WeakBag, dataGet, dataSet } from './util/utils.js'
import { removeUpload, upload, uploadMultiple } from './features/supportFileUploads.js'

let properties = {}
let fallback

function raxmProperty(name, callback, component = null) {
    properties[name] = callback
}

function raxmFallback(callback) {
    fallback = callback
}

// // For V2 backwards compatibility...
// // And I actually like both depending on the scenario...
let aliases = {
    'on': '$on',
    'get': '$get',
    'set':  '$set',
    'call':  '$call',
    'sync':   '$sync',
    'watch':   '$watch',
    'upload':   '$upload',
    'commit':    '$commit',
    'entangle':   '$entangle',
    'dispatch':    '$dispatch',
    'dispatchTo':   '$dispatchTo',
    'dispatchSelf':  '$dispatchSelf',
    'removeUpload':   '$removeUpload',
    'uploadMultiple':  '$uploadMultiple',
}

function getProperty(component, name) {
    return properties[name](component)
}

function getFallback(component) {
    return fallback(component)
}
 
raxmProperty('__instance', (component) => component)

raxmProperty('$get', (component) => (property) => get(component, property))

raxmProperty('$set', (component) => {
    set(component, property, value, defer, skipWatcher)
})

raxmProperty('$call', (component) => async (method, ...params) => {
    return await addMethodAction(component, method, ...params)
})

raxmProperty('$entangle', (component) => (name) => {
    return generateEntangleFunction(component)(name)
})

raxmProperty('$toggle', (component) => (name) => {
    return set(component, name, ! component.$raxm.get(name))
})

raxmProperty('$watch', (component) => (path, callback) => {
    let firstTime = true
    let oldValue = undefined

    // JSON.stringify touches every single property at any level enabling deep watching
    let value = dataGet(component.serverMemo.data, path)
    // JSON.stringify(value)

    if (! firstTime) {
        // We have to queue this watcher as a microtask so that
        // the watcher doesn't pick up its own dependencies.
        queueMicrotask(() => {
            callback(value, oldValue)

            oldValue = value
        })
    } else {
        oldValue = value
    }

    firstTime = false
})

raxmProperty('$refresh', (component) => (...params) => addMethodAction(component, '$refresh', ...params))
raxmProperty('$on', (component) => (...params) => listen(component, ...params))
raxmProperty('$dispatch', (component) => (...params) => dispatch(component, ...params))
raxmProperty('$dispatchSelf', (component) => (...params) => dispatchSelf(component, ...params))
raxmProperty('$dispatchTo', (component) => (...params) => dispatchTo(component, ...params))
raxmProperty('$upload', (component) => (...params) => upload(component, ...params))
raxmProperty('$uploadMultiple', (component) => (...params) => uploadMultiple(component, ...params))
raxmProperty('$removeUpload', (component) => (...params) => removeUpload(component, ...params))

let parentMemo = new WeakMap

raxmProperty('$parent', component => {
    if (parentMemo.has(component)) return parentMemo.get(component).$raxm

    let parent = closestComponent(component.el.parentElement)

    parentMemo.set(component, parent)

    return parent.$raxm
})


let overriddenMethods = new WeakMap

export function overrideMethod(component, method, callback) {
    if (! overriddenMethods.has(component)) {
        overriddenMethods.set(component, {})
    }

    let obj = overriddenMethods.get(component)

    obj[method] = callback

    overriddenMethods.set(component, obj)
}

raxmFallback((component) => (property) => async (...params) => {
    // If this method is passed directly to a Vue or Alpine
    // event listener (@click="someMethod") without using
    // parens, strip out the automatically added event.
    if (params.length === 1 && params[0] instanceof Event) {
        params = []
    }

    if (overriddenMethods.has(component)) {
        let overrides = overriddenMethods.get(component)

        if (typeof overrides[property] === 'function') {
            return overrides[property](params)
        }
    }

    return await requestCall(component, property, params)
})

export function generateRaxmObject(component, state) {
    return new Proxy({}, {
        get(object, property) {
            if (property === '__instance') return component

            if (property in aliases) {
                return (...args) => getProperty(component, aliases[property])( ...args)
            }
            else if (property in properties) {
                return (...args) => getProperty(component, property)( ...args)
            } else if (property in state) {
                return state[property]
            } else if (! ['then'].includes(property)) {
                return getFallback(component)(property)
            }
        },

        set(obj, property, value) {
            if (property in state) {
                state[property] = value
            }
            return true
        },
    });
}
