
export function kebabCase(subject) {
    return subject.replace(/([a-z])([A-Z])/g, '$1-$2').replace(/[_\s]/, '-').toLowerCase()
}

export class Bag {
    constructor() { this.arrays = {} }

    add(key, value) {
        if (! this.arrays[key]) this.arrays[key] = []
        this.arrays[key].push(value)
    }

    get(key) { return this.arrays[key] || [] }

    each(key, callback) { return this.get(key).forEach(callback) }
}

export class WeakBag {
    constructor() { this.arrays = new WeakMap }

    add(key, value) {
        if (! this.arrays.has(key) ) this.arrays.set(key, [])
        this.arrays.get(key).push(value)
    }

    get(key) { return this.arrays.has(key) ? this.arrays.get(key) : [] }

    each(key, callback) { return this.get(key).forEach(callback) }
}

export class MessageBus {
    constructor() {
        this.listeners = {}
    }

    register(name, callback) {
        if (! this.listeners[name]) {
            this.listeners[name] = []
        }

        this.listeners[name].push(callback)
    }

    call(name, ...params) {
        (this.listeners[name] || []).forEach(callback => {
            callback(...params)
        })
    }

    has(name) {
        return Object.keys(this.listeners).includes(name)
    }
}

export function debounce(func, wait, immediate) {
    var timeout
    return function () {
        var context = this,
            args = arguments
        var later = function () {
            timeout = null
            if (!immediate) func.apply(context, args)
        }
        var callNow = immediate && !timeout
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
        if (callNow) func.apply(context, args)
    }
}

export function dispatch(eventName) {
    const event = document.createEvent('Events')

    event.initEvent(eventName, true, true)

    document.dispatchEvent(event)

    return event
}

// A little DOM-tree walker.
// (TreeWalker won't do because I need to conditionaly ignore sub-trees using the callback)
export function walk(root, callback) {

    const visited = new Set();

    const stack = [root];
    while (stack.length) {
        const node = stack.pop();
        if (visited.has(node)) continue;

        visited.add(node);

        if (callback(node) === false) return;
        stack.push(...node.children);
    }
}

/**
 * Type-checking in JS is weird and annoying, these are better.
 */
export function isObjecty(subject)   { return (typeof subject === 'object' && subject !== null) }
export function isObject(subject)    { return (isObjecty(subject) && ! isArray(subject)) }
export function isArray(subject)     { return Array.isArray(subject) }
export function isFunction(subject)  { return typeof subject === 'function' }
export function isPrimitive(subject) { return typeof subject !== 'object' || subject === null }

/**
 * An easy way to loop through arrays and objects.
 */
export function each(subject, callback) {
    Object.entries(subject).forEach(([key, value]) => callback(key, value))
}

/**
 * Get a property from an object with support for dot-notation.
 */
export function dataGet(object, key) {
    if (key === '') return object

    return key.split('.').reduce((carry, i) => {
        if (carry === undefined) return undefined

        return carry[i]
    }, object)
}

/**
 * Set a property on an object with support for dot-notation.
 */
export function dataSet(object, key, value) {
    let segments = key.split('.')

    if (segments.length === 1) {
        return object[key] = value
    }

    let firstSegment = segments.shift()
    let restOfSegments = segments.join('.')

    if (object[firstSegment] === undefined) {
        object[firstSegment] = {}
    }

    dataSet(object[firstSegment], restOfSegments, value)
}

export function call(method, params, context = window) {
    try {
        if (typeof method !== 'string') 
            throw new Error('The method provided is not a string')
    
        if (typeof context !== 'object') 
            throw new Error('The context provided is not an object')
    
        const func = context[method]
        if (typeof func === 'function') {
            return func.call(context, ...params)
        } else {
            console.error(`The function '${method}' does not exist in the provided context.`)
        }
    } catch (error) {
        console.error(`An error occurred when calling the function '${method}':`, error)
    }
}

/**
 * Post requests in Raxm require a csrf token to be passed
 * along with the payload. Here, we'll try and locate one.
 */
export function getCsrfToken() {
    // Purposely not caching. Fetching it fresh every time ensures we're
    // not depending on a stale session's CSRF token...

    if (document.querySelector('meta[name="csrf-token"]')) {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }

    if (document.querySelector('[data-csrf]')) {
        return document.querySelector('[data-csrf]').getAttribute('data-csrf')
    }

    if (window.raxmScriptConfig['csrf'] ?? false) {
        return window.raxmScriptConfig['csrf']
    }

    throw 'Raxm: No CSRF token detected'
}

export function contentIsFromDump(content) {
    return !! content.match(/<script>Sfdump\(".+"\)<\/script>/)
}

export function splitDumpFromContent(content) {
    let dump = content.match(/.*<script>Sfdump\(".+"\)<\/script>/s)

    return [dump, content.replace(dump, '')]
}
