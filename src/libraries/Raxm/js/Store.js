import EventAction from './action/event.js'
import HookManager from './HookManager.js'
import { MessageBus }  from './util/utils.js'
import { PREFIX_REGEX } from './directives.js'

const store = {
    componentsById: {},
    listeners: new MessageBus(),
    initialRenderIsFinished: false,
    raxmIsInBackground: false,
    raxmIsOffline: false,
    sessionHasExpired: false,
    sessionHasExpiredCallback: undefined,
    hooks: HookManager,
    onErrorCallback: () => {},

    components() {
        return Object.keys(this.componentsById).map(key => {
            return this.componentsById[key]
        })
    },

    addComponent(component) {
        return (this.componentsById[component.id] = component)
    },

    findComponent(id) {
        return this.componentsById[id]
    },

    getComponentsByName(name) {
        return this.components().filter(component => {
            return component.name === name
        })
    },

    hasComponent(id) {
        return !!this.componentsById[id]
    },

    tearDownComponents() {
        this.components().forEach(component => {
            this.removeComponent(component)
        })
    },

    on(event, callback) {
        this.listeners.register(event, callback)
    },

    emit(event, ...params) {
        this.listeners.call(event, ...params)

        this.componentsListeningForEvent(event).forEach(component =>
            component.addAction(new EventAction(event, params))
        )
    },

    emitUp(el, event, ...params) {
        this.componentsListeningForEventThatAreTreeAncestors(
            el, event
        ).forEach(component =>
            component.addAction(new EventAction(event, params))
        )
    },

    emitSelf(componentId, event, ...params) {
        let component = this.findComponent(componentId)

        if (component.listeners.includes(event)) {
            component.addAction(new EventAction(event, params))
        }
    },

    emitTo(componentName, event, ...params) {
        let components = this.getComponentsByName(componentName)

        components.forEach(component => {
            if (component.listeners.includes(event)) {
                component.addAction(new EventAction(event, params))
            }
        })
    },

    componentsListeningForEventThatAreTreeAncestors(el, event) {
        var parentIds = []
        var parent = el.parentElement.closest(`[${PREFIX_REGEX}id]`)

        while (parent) {
            parentIds.push(parent.getAttribute(`${PREFIX_REGEX}id`))

            parent = parent.parentElement.closest(`[${PREFIX_REGEX}id]`)
        }

        return this.components().filter(component => {
            return (
                component.listeners.includes(event) &&
                parentIds.includes(component.id)
            )
        })
    },

    componentsListeningForEvent(event) {
        return this.components().filter(component => {
            return component.listeners.includes(event)
        })
    },

    registerHook(name, callback) {
        this.hooks.register(name, callback)
    },

    callHook(name, ...params) {
        this.hooks.call(name, ...params)
    },

    changeComponentId(component, newId) {
        let oldId = component.id

        component.id = newId
        component.fingerprint.id = newId

        this.componentsById[newId] = component

        delete this.componentsById[oldId]

        // Now go through any parents of this component and change
        // the component's child id references.
        this.components().forEach(component => {
            let children = component.serverMemo.children || {}

            Object.entries(children).forEach(([key, { id, tagName }]) => {
                if (id === oldId) {
                    children[key].id = newId
                }
            })
        })
    },

    removeComponent(component) {
        // Remove event listeners attached to the DOM.
        component.tearDown()
        // Remove the component from the store.
        delete this.componentsById[component.id]
    },

    onError(callback) {
        this.onErrorCallback = callback
    },

    getClosestParentId(childId, subsetOfParentIds) {
        let distancesByParentId = {}

        subsetOfParentIds.forEach(parentId => {
            let distance = this.getDistanceToChild(parentId, childId)

            if (distance) distancesByParentId[parentId] = distance
        })

        let smallestDistance = Math.min(...Object.values(distancesByParentId))
        let closestParentId

        Object.entries(distancesByParentId).forEach(([parentId, distance]) => {
            if (distance === smallestDistance) closestParentId = parentId
        })

        return closestParentId
    },

    closestComponent(el, strict = true) {
        let currentElement = el;
    
        while (currentElement) {
            if (currentElement.__raxm) {
                return currentElement.__raxm;
            }
    
            currentElement = currentElement.parentElement;
        }
    
        if (strict) {
            throw new Error("Could not find Raxm component in DOM tree");
        }
    },    

    getDistanceToChild(parentId, childId, distanceMemo = 1) {
        let parentComponent = this.findComponent(parentId)

        if (!parentComponent) return

        let childIds = parentComponent.childIds

        if (childIds.includes(childId)) return distanceMemo

        for (let i = 0; i < childIds.length; i++) {
            let distance = this.getDistanceToChild(childIds[i], childId, distanceMemo + 1)

            if (distance) return distance
        }
    },    
}

export default store

export function find(id) {
    return store.findComponent(id)
}

export function first() {
    return store.components()[0]
}

export function getByName(name) {
    return store.getComponentsByName(name).map(i => i.$raxm)
}

export function all() {
    return store.components()
}

export function on(event, callback) {
    return store.on(event, callback)
}

export function hook(name, callback) {
    return store.registerHook(name, callback)
}

export function trigger(name, params) {
    return store.callHook(name, ...params)
}

export function closestComponent(el, strict = true) {
    return store.closestComponent(el, strict)
}
