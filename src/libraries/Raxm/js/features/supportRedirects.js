import store from '../store.js'
import { shouldRedirectUsingNavigateOr } from './supportNavigate.js'

store.registerHook('message.received', (message, component) => {
    let effects = message.response.effects

    if (! effects['redirect']) return

    shouldRedirectUsingNavigateOr(effects, url, () => {
        window.location.href = url
    })
})