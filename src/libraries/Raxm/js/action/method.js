import Action from './index.js'

export default class extends Action {
    constructor(method, params, el, skipWatcher = false) {
        super(el, skipWatcher)

        this.type = 'callMethod'
        this.method = method
        this.payload = {
            id: this.signature,
            method,
            params,
        }
    }
}
