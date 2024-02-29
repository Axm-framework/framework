import { directive, PREFIX_REGEX, PREFIX_DISPLAY } from '../directives.js'
import DeferredModelAction from '../action/model.js'
import ModelAction from '../action/model.js'
import store from '../store.js'
import { addAction, modelSyncDebounce } from '../commit.js'
import DOM from '../dom/dom.js'
import { handleFileUpload } from '../features/supportFileUploads.js'

directive('model', ({ el, directive, component, cleanup }) => {
    let { expression, modifiers } = directive

    if (!expression) {
        return console.warn(`Raxm: [${PREFIX_DISPLAY}model] is missing a value.`, el)
    }

    if (componentIsMissingProperty(component, expression)) {
        return console.warn(`Raxm: [${PREFIX_DISPLAY}model="`+expression+`"] property does not exist on component: [`+component.name+`]`, el)
    }

    // Handle file uploads differently...
    if (el.type && el.type.toLowerCase() === 'file') {
        return handleFileUpload(el, expression, component, cleanup)
    }  
        
    DOM.setInputValueFromModel(el, component)

    attachModelListener(el, directive, component)
})

function attachModelListener(el, directive, component) {
    let { expression, modifiers } = directive

    // This is used by morphdom: morphdom.js:391
    el.isRaxmModel = true

    let isLive      = modifiers.includes('live')
    let isLazy      = modifiers.includes('lazy')
    let isDefer     = modifiers.includes('defer')
    let isDebounced = modifiers.includes('debounce')


    store.callHook('interceptRaxmModelAttachListener', directive, el, component, expression)
 
    const event = el.tagName === 'SELECT'
        || ['checkbox', 'radio'].includes(el.type)
        || isLazy ? 'change' : 'input'

    const debounceIf = (condition, callback, time) => {
        return condition
            ? modelSyncDebounce(callback, time)
            : callback
    }

    // If it's a text input and not .lazy, debounce, otherwise fire immediately.
    let handler = debounceIf(DOM.isTextInput(el) && ! isDebounced && ! isLazy , e => {
        let model = directive.value
        let el    = e.target

        let value = e instanceof CustomEvent
            // We have to check for typeof e.detail here for IE 11.
            && typeof e.detail != 'undefined'
            && typeof window.document.documentMode == 'undefined'
            // With autofill in Safari, Safari triggers a custom event and assigns
            // the value to e.target.value, so we need to check for that value as well.
            ? e.detail ?? e.target.value
            : DOM.valueFromInput(el, component)

        if (isDefer) {
            addAction(component, new DeferredModelAction(model, value, el))
        } else {
            addAction(component, new ModelAction(model, value, el))
        }

    }, directive.durationOr(150))


    el.addEventListener(event, handler)

    component.addListenerForTeardown(() => {
        el.removeEventListener(event, handler)
    })

    // Taken from: https://stackoverflow.com/questions/9847580/how-to-detect-safari-chrome-ie-firefox-and-opera-browser
    let isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent)

    // Safari is weird and doesn't properly fire input events when
    // a user "autofills" a axm:model(.lazy) field. So we are
    // firing them manually for assurance.
    isSafari && el.addEventListener('animationstart', e => {
        if (e.animationName !== 'raxmautofill') return

        e.target.dispatchEvent(new Event('change', { bubbles: true }))
        e.target.dispatchEvent(new Event('input',  { bubbles: true }))
    })
}

function componentIsMissingProperty(component, property) {
    if (property.startsWith('$parent')) {
        let parent = closestComponent(component.el.parentElement, false)

        if (! parent) return true

        return componentIsMissingProperty(parent, property.split('$parent.')[1])
    }

    let baseProperty = property.split('.')[0]

    return ! Object.keys(component.serverMemo.data).includes(baseProperty)
}
