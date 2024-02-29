import { toggleBooleanStateDirective } from './shared.js'
import { directive, getDirectives } from '../directives.js'
import store from '../store.js'

directive('loading', ({ el, directive, component }) => {
    let targets = getTargets(el)

    let [delay, abortDelay] = applyDelay(directive)
    const start = () =>
        delay(() => toggleBooleanStateDirective(el, directive, true))
    const end = () =>
        abortDelay(() => toggleBooleanStateDirective(el, directive, false))

    whenTargetsArePartOfRequest(component, targets, [start, end])
    whenTargetsArePartOfFileUpload(component, targets, [start, end])
})

function applyDelay(directive) {
    if (!directive.modifiers.includes('delay') || directive.modifiers.includes('none'))
        return [(i) => i(), (i) => i()]

    let duration = 200

    let delayModifiers = {
        shortest: 50,
        shorter: 100,
        short: 150,
        default: 200,
        long: 300,
        longer: 500,
        longest: 1000,
    }

    if (Object.keys(delayModifiers).includes(directive.modifiers[0])) {
        duration = delayModifiers[directive.modifiers[0]]
    }

    let timeout
    let started = false

    return [
        (callback) => {
            // Initiate delay...
            timeout = setTimeout(() => {
                callback()

                started = true
            }, duration)
        },
        (callback) => {
            // Execute or abort...
            if (started) {
                callback()
            } else {
                clearTimeout(timeout)
            }
        },
    ]
}

function whenTargetsArePartOfRequest(
    iComponent,
    targets,
    [startLoading, endLoading]
) {
    const hookCallbackStart = (message, component) => {
        if (iComponent !== component) return
        const payload = message.updateQueue[0].payload
        if (targets.length > 0 && !containsTargets(payload, targets)) return
        startLoading()
    }

    const hookCallbackEnd = () => {
        endLoading()
    }

    store.registerHook('message.sent', hookCallbackStart)
    store.registerHook('message.failed', hookCallbackEnd)
    store.registerHook('message.received', hookCallbackEnd)
    store.registerHook('element.removed', hookCallbackEnd)
}

function whenTargetsArePartOfFileUpload(
    component,
    targets,
    [startLoading, endLoading]
) {
    let eventMismatch = (e) => {
        let { id, property } = e.detail

        if (id !== component.id) return true
        if (
            targets.length > 0 &&
            !targets.map((i) => i.target).includes(property)
        )
            return true

        return false
    }

    window.addEventListener('raxm-upload-start', (e) => {
        if (eventMismatch(e)) return

        startLoading()
    })

    window.addEventListener('raxm-upload-finish', (e) => {
        if (eventMismatch(e)) return

        endLoading()
    })

    window.addEventListener('raxm-upload-error', (e) => {
        if (eventMismatch(e)) return

        endLoading()
    })
}

function containsTargets(payload, targets) {
    let { name, method, params } = payload

    let target = targets.find(({ target, tparams }) => {
        if (tparams) {
            return (
                target === method &&
                tparams === quickHash(JSON.stringify(params))
            )
        }

        return name === target || method === target
    })

    return target !== undefined
}

function getTargets(el) {
    let directives = getDirectives(el)

    let targets = []

    if (directives.has('target')) {
        let directive = directives.get('target')

        let raw = directive.expression

        if (raw.includes('(') && raw.includes(')')) {
            targets.push({
                target: directive.method,
                params: quickHash(JSON.stringify(directive.params)),
            })
        } else if (raw.includes(',')) {
            raw.split(',')
                .map((i) => i.trim())
                .forEach((target) => {
                    targets.push({ target })
                })
        } else {
            targets.push({ target: raw })
        }
    } else {
        let nonActionOrModelRaxmDirectives = [
            'init',
            'dirty',
            'offline',
            'target',
            'loading',
            'poll',
            'ignore',
            'key',
            'id',
        ]

        targets = directives
            .all()
            .filter(
                (i) =>
                    !nonActionOrModelRaxmDirectives.includes(i.value) &&
                    i.expression.split('(')[0]
            )
            .map((i) => ({ target: i.expression.split('(')[0] }))
    }

    return targets
}

function quickHash(subject) {
    return btoa(encodeURIComponent(subject))
}
