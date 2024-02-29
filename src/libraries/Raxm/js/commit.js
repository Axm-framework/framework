import { debounce } from './util/utils.js'
import { PrefetchMessage } from './message.js'
import MethodAction from './action/method.js'
import ModelAction from './action/model.js'
import DeferredModelAction from './action/deferred-model.js'

export async function addMethodAction(component, method, ...params) {
    return new Promise((resolve, reject) => {
        let action = new MethodAction(method, params, component.el)

        addAction(component, action)

        action.onResolve(thing => resolve(thing))
        action.onReject(thing  => reject(thing))
    })
}
  
export function addAction(component, action) {
    if (action instanceof DeferredModelAction) {
        component.deferredActions[action.name] = action
        return
    }

    if (
        component.prefetchManager.actionHasPrefetch(action) &&
        component.prefetchManager.actionPrefetchResponseHasBeenReceived(action)
    ) {
        const message = component.prefetchManager.getPrefetchMessageByAction(action)
        component.handleResponse(message)
        component.prefetchManager.clearPrefetches()
        return
    }

    component.updateQueue.push(action)

    // This debounce is here in-case two events fire at the "same" time:
    // For example: if you are listening for a click on element A,
    // and a "blur" on element B. If element B has focus, and then,
    // you click on element A, the blur event will fire before the "click"
    // event. This debounce captures them both in the actionsQueue and sends
    // them off at the same time.
    // Note: currently, it's set to 5ms, that might not be the right amount, we'll see.
    debounce(component.fireMessage, 5).apply(component)

    // Clear prefetches.
    component.prefetchManager.clearPrefetches()
}

export function get(component, name) {
    return name
        .split('.')
        .reduce((carry, segment) => typeof carry === 'undefined' 
        ? carry : carry[segment], component.data)
}

export async function set(component, name, value, defer = false, skipWatcher = false) {
    if (defer) {
        addAction(component, new DeferredModelAction(name, value, component.el, skipWatcher))
    } else {
        addAction(component, new MethodAction('$set', [name, value], component.el, skipWatcher))
    }
}

export async function sync(component, name, value, defer = false) {
    if (defer) {
        addAction(component, new DeferredModelAction(name, value, component.el))
    } else {
        addAction(component, new ModelAction(name, value, component.el))
    }
}

export function modelSyncDebounce(callback, time) {
    // Prepare yourself for what's happening here.
    // Any text input with axm:model on it should be "debounced" by ~150ms by default.
    // We can't use a simple debounce function because we need a way to clear all the pending
    // debounces if a user submits a form or performs some other action.
    // This is a modified debounce function that acts just like a debounce, except it stores
    // the pending callbacks in a global property so we can "clear them" on command instead
    // of waiting for their setTimeouts to expire. I know.
    let modelDebounceCallbacks = []

    // This is a "null" callback. Each axm:model will resister one of these upon initialization.
    let callbackRegister = { callback: () => {} }
    modelDebounceCallbacks.push(callbackRegister)

    let timeout
    return e => {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
            callback(e)
            timeout = undefined
            callbackRegister.callback()
        }, time)
        callbackRegister.callback = () => clearTimeout(timeout)
    }
}

export function callAfterModelDebounce(callback, modelDebounceCallbacks) {
    if (modelDebounceCallbacks) {
        modelDebounceCallbacks.forEach(callbackRegister => {
            callbackRegister.callback()
        })
    }
    callback()
}

export function addPrefetchAction(component, action) {
    if (component.prefetchManager.actionHasPrefetch(action)) return

    const message = new PrefetchMessage(component, action)

    component.prefetchManager.addMessage(message)
    component.connection.sendMessage(message)
}
