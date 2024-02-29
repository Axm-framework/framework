import store from '../store.js'
import componentStore from '../store.js'
import { getCsrfToken, contentIsFromDump, splitDumpFromContent } from '../util/utils.js'
import { showHtmlModal } from '../modal.js'

let updateUri = document.querySelector('[data-baseUrl]')?.getAttribute('data-baseUrl') ?? window.raxmScriptConfig['baseUrl'] ?? null

export default class Connection {
    constructor() {
        this.headers = {}
    }

    onMessage(message, payload) {
        message.component.receiveMessage(message, payload)
    }

    onError(message, status, response) {
        message.component.messageSendFailed()

        return componentStore.onErrorCallback(status, response)
    }

    showExpiredMessage(response, message) {
        if (store.sessionHasExpiredCallback) {
            store.sessionHasExpiredCallback(response, message)
        } else {
            confirm(
                'This page has expired.\nWould you like to refresh the page?'
            ) && window.location.reload()
        }
    }

    async sendMessage(message) {
        const data   = message.payload()
        const csrfToken = getCsrfToken()
        const url = updateUri
        const method = 'POST'

        try {
            const headers  = this.buildHeaders(csrfToken, this.headers)
            const response = await fetch(`${url}/raxm/update/${data.fingerprint.name}`, {

                method: method,
                body: JSON.stringify(data),
                credentials: 'same-origin',
                headers,
            });
    
            /**
             * Sometimes a redirect happens on the backend outside of Raxm's control,
             * for example to a login page from a middleware, so we will just redirect
             * to that page.
             */
            if (response.redirected) {
                window.location.href = response.url
            }

            if (response.ok) {
                const responseText = await response.text();

                if (contentIsFromDump(responseText)) {
                    [dump, content] = splitDumpFromContent(responseText)
            
                    this.onError(message)
                    showHtmlModal(dump)

                } else {
                    this.onMessage(message, JSON.parse(responseText))
                }

            } else {
                this.handleErrorResponse(response, message, this)
            }

        } catch (error) {
            this.onError(message)
        }
    }    
    
    buildHeaders(csrfToken, customHeaders) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'text/html, application/xhtml+xml',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Axm': true,

            ...(customHeaders),
        };
    
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken
        }
       
        return headers
    }
    
    handleErrorResponse(response, message, context) {
      
        if (context.onError(message, response.status, response) === false) return
   
        if (response.status === 419 && !store.sessionHasExpired) {
            store.sessionHasExpired = true
            context.showExpiredMessage(response, message)
       
        } else {

            response.text().then(responseText => {
                showHtmlModal(responseText)
            })
        }
    }
   
}
