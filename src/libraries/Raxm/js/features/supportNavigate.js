import Progress from '../progress.js'
import { navigateTo } from '../directives/axm-navigate.js'

let isNavigating = false
let progressBar = true

shouldHideProgressBar() && disableProgressBar()

export function shouldRedirectUsingNavigateOr(effects, url, or) {
    let forceNavigate = effects.redirectUsingNavigate

    if (forceNavigate || isNavigating) {
        navigateTo(url)
    } else {
        or()
    }
}

function shouldHideProgressBar() {
    if (!! document.querySelector('[data-no-progress-bar]')) return true

    if (progressBar) return true

    return false
}

function disableProgressBar() {
    if (progressBar) {
        Progress.init()
    }
}
