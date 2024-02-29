class DependencyTracker {
    constructor() {
        this.tracked = new Map()
    }
  
    add(proxy) {
        this.tracked.set(proxy, new Set())
    }
  
    remove(proxy) {
        this.tracked.delete(proxy)
    }
  
    forEach(fn) {
        this.tracked.forEach(fn)
    }
  
    checkCircularDependencies() {
        for (const proxy of this.tracked.values()) {
            if (proxy._st().observers.has('_st')) {
                throw new Error('Circular dependency detected')
            }
        }
    }
}
  
const isReactive = (value) => value instanceof Object && '_st' in value
const isReactiveFunction = (fn) => typeof fn === 'function' && fn.__isReactive === true
const delay = (fn, ...args) => {

    return new Promise((resolve, reject) => {
        const id = setTimeout(() => {
            try {
                fn(...args)
                resolve()
            } catch (error) {
                reject(error)
            }
        }, 0)

        return {
            resolve,
            reject,
            clear: () => clearTimeout(id),
        }
    })
}
  
const mergeReactive = (a, b) => {
    const merged = { ...b }

    for (const key in a) {
        if (isReactive(a[key])) {
            merged[key] = mergeReactive(a[key], b[key])
        }
    }

    return merged
}
  
class ReactiveProxy {
    constructor(data, state = {}) {
        this.data = data
        this.state = state
        this.observers = new Map()
        this.observerProperties = new Map()
        this.children = []
    
        this._st = () => ({
            observers,
            observerProperties,
            data,
            state,
        })
    }
  
    get(target, key) {
        this.observerProperties.has(key) && this.observerProperties.get(key).add(this)
        return target[key]
    }
  
    set(target, key, value) {
        if (target[key] !== value) {
            target[key] = value
            this.notifyObservers(key, value)
        }
        return true
    }
  
    deleteProperty(target, key) {
        if (key in target) {
            delete target[key]
            this.notifyObservers(key, undefined)
        }
        return true
    }
  
    notifyObservers(key, value) {
        if (this.observers.has(key)) {
            this.observers.get(key).forEach((observer) => observer(key, value))
        }
    }
  
    on(event) {
        const dep = (a, c) => {
            observers.has(a) && observers.get(a).add(c)
            observerProperties.has(c) && observerProperties.get(c).add(a)
        }
  
        const handler = () => {
            this.notifyObservers(event, undefined)
        }
  
        return dep(event, handler)
    }
  
    off(event) {
        const handler = () => {
            this.notifyObservers(event, undefined)
        }
  
        return () => {
            if (this.observers.has(event)) {
            this.observers.get(event).delete(handler)
            }
        }
    }
}
  
const createReactive = (data, state = {}) => {
    if (isReactive(data)) {
        return data
    }
  
    return new ReactiveProxy(data, state)
}
  
const dependencyTracker = new DependencyTracker()
  
export {
    createReactive,
    isReactive,
    isReactiveFunction,
    delay,
    mergeReactive,
    dependencyTracker,
}
