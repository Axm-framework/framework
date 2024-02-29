import { directive } from "../directives.js"
import DOM from "../dom/dom.js"


directive('navigate', ({ el, directive, component }) => {
    el.addEventListener('click', navigationManager.handleNavigate)
})

const MAX_HISTORY_LENGTH = 10
const attributesExemptFromScriptTagHashing = ['data-csrf']

class NavigationManager {
    constructor() {
        this.oldBodyScriptTagHashes = []
        this.handleNavigate = this.handleNavigate.bind(this);
        window.addEventListener('popstate', this.handlePopState.bind(this))
    }

    handleNavigate(event) {
        if (this.shouldInterceptClick(event)) return
        event.preventDefault()

        const newUrl = event.target.getAttribute('href')
        this.navigateTo(newUrl)
    }

    shouldInterceptClick(event) {
        return (
            event.which > 1 ||
            event.altKey  ||
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey
        )
    }

    async navigateTo(url) {
                
        this.updateHistoryStateForCurrentPage()

        // change view
        const response = await loadView(url)

        const pageState = { html: response.html }
        const urlObject = new URL(url, document.baseURI)

        if (window.location.href === urlObject.href) {
            this.replaceState(urlObject, pageState.html); 
        }else{
            this.pushState(urlObject, pageState.html);
        }

        renderView(response.html)
    }

    handlePopState(e) {
        const state = e.state
        if (state && state.raxm && state.raxm._html) {
            renderView(this.fromSessionStorage(state.raxm._html))
        } else {
            this.navigateTo(window.location.href, true)
            return
        }
        dispatchEvent(new Event('raxm:popstate'))
        window.Raxm.start()
    }

    updateHistoryStateForCurrentPage() {
        const currentPageUrl = new URL(window.location.href, document.baseURI)
        const currentState = {
            html: document.documentElement.outerHTML
        }

        this.replaceState(currentPageUrl, currentState.html)
    }

    pushState(url, html) {
        this.updateState('pushState', url, html)
    }

    replaceState(url, html) {
        this.updateState('replaceState', url, html)
    }

    updateState(method, url, html) {
        this.clearState()

        let key = (new Date).getTime()
        this.tryToStoreInSession(key, html)
        let state = history.state || {}
        if (!state.raxm) state.raxm = {}
        state.raxm._html = key
        try {
            // 640k character limit:
            history[method](state, document.title, url)
        } catch (error) {
            if (error instanceof DOMException && error.name === 'SecurityError') {
                console.error('Raxm: You can\'t use axm:navigate with a link to a different root domain: ' + url)
            }
        }
    }

    clearState() {
        const currentHistory = window.history.state || {}
        const historyData = currentHistory.raxm || []
        if (historyData.length >= MAX_HISTORY_LENGTH) {
            window.history.go(-1)
            historyData.shift()
            currentHistory.raxm = historyData
            window.history.replaceState(currentHistory, document.title, window.location.href)
        }
    }

    fromSessionStorage(timestamp) {
        let state = JSON.parse(sessionStorage.getItem('raxm:' + timestamp))
        return state
    }

    tryToStoreInSession(timestamp, value) {
        try {
            sessionStorage.setItem('raxm:' + timestamp, JSON.stringify(value))
        } catch (error) {
            if (![22, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14].includes(error.code)) return
            let oldestTimestamp = Object.keys(sessionStorage)
                .map(key => Number(key.replace('raxm:', '')))
                .sort()
                .shift()
            if (!oldestTimestamp) return
            sessionStorage.removeItem('raxm:' + oldestTimestamp)
            this.tryToStoreInSession(timestamp, value)
        }
    }
}

const navigationManager = new NavigationManager()

// Cargar vista 
async function loadView(url) {
    document.dispatchEvent(new Event('raxm:navigating'))
    try {
        const response = await fetch(url)
        const html = await response.text()
        return { html }
    } catch (error) {
        console.error('Error loading view:', error)
        return { html: '' }
    }
}

async function renderView(html) {
    const newDocument = (new DOMParser()).parseFromString(html, "text/html")
    const newBody = document.adoptNode(newDocument.body)
    const newHead = document.adoptNode(newDocument.head)
    const newBodyScriptTagHashes = Array.from(newBody.querySelectorAll('script')).map(i => {
        return simpleHash(DOM.ignoreAttributes(i.outerHTML, attributesExemptFromScriptTagHashing))
    })
    mergeNewHead(newHead)
    prepNewBodyScriptTagsToRun(newBody, newBodyScriptTagHashes)
    const oldBody = document.body
    document.body.replaceWith(newBody)
    document.dispatchEvent(new CustomEvent('raxm:navigated', { detail: { visit: { completed: true } } }))
}

function simpleHash(str) {
    return str.split('').reduce((a, b) => {
        a = ((a << 5) - a) + b.charCodeAt(0)
        return a & a
    }, 0)
}

function mergeNewHead(newHead) {
    const children = Array.from(document.head.children)
    const headChildrenHtmlLookup = children.map(i => i.outerHTML)
    const garbageCollector = document.createDocumentFragment()
    const touchedHeadElements = []
    for (const child of Array.from(newHead.children)) {
        if (DOM.isAsset(child)) {
            if (!headChildrenHtmlLookup.includes(child.outerHTML)) {
                if (isTracked(child)) {
                    if (ifTheQueryStringChangedSinceLastRequest(child, children)) {
                        setTimeout(() => window.location.reload())
                    }
                }
                if (DOM.isScript(child)) {
                    document.head.appendChild(DOM.cloneScriptTag(child))
                } else {
                    document.head.appendChild(child)
                }
            } else {
                garbageCollector.appendChild(child)
            }
            touchedHeadElements.push(child)
        }
    }
    for (const child of Array.from(document.head.children)) {
        if (! DOM.isAsset(child)) child.remove()
    }
    for (const child of Array.from(newHead.children)) {
        document.head.appendChild(child)
    }
}

function ifTheQueryStringChangedSinceLastRequest(el, currentHeadChildren) {
    let [uri, queryString] = extractUriAndQueryString(el)

    return currentHeadChildren.some(child => {
        if (! isTracked(child)) return false

        let [currentUri, currentQueryString] = extractUriAndQueryString(child)

        // Only consider a data-navigate-track element changed if the query string has changed (not the URI)...
        if (currentUri === uri && queryString !== currentQueryString) return true
    })
}

function extractUriAndQueryString(el) {
    let url = DOM.isScript(el) ? el.src : el.href

    return url.split('?')
}

function prepNewBodyScriptTagsToRun(newBody, newBodyScriptTagHashes) {
    newBody.querySelectorAll('script').forEach(i => {
        if (i.hasAttribute('data-navigate-once')) {
            let hash = simpleHash(
                DOM.ignoreAttributes(i.outerHTML, attributesExemptFromScriptTagHashing)
            )
            if (newBodyScriptTagHashes.includes(hash)) return
        }
        i.replaceWith(DOM.cloneScriptTag(i))
    })
}

function isTracked(el) {
    return el.hasAttribute('data-navigate-track')
}

export function pushState(url, html) {
    navigationManager.pushState(url, html)
}

export function replaceState(url, html) {
    navigationManager.replaceState(url, html)
}

export function navigateTo(url) {
    navigationManager.navigateTo(url, true)
}
