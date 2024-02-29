import { MessageBus } from './util/utils.js'

export default {
    availableHooks: [
        /**
         * Public Hooks
         */
        'component.initialized',
        'directive.initialized',
        'element.initialized',
        'element.updating',
        'element.updated',
        'element.removed',
        'message.sent',
        'message.failed',
        'message.received',
        'message.processed',

        /**
         * Private Hooks
         */
        'interceptRaxmModelSetValue',
        'interceptRaxmModelAttachListener',
        'beforeReplaceState',
        'beforePushState',
    ],

    bus: new MessageBus(),

    register(name, callback) {
        if (! this.availableHooks.includes(name)) {
            throw `Raxm: Referencing unknown hook: [${name}]`
        }

        this.bus.register(name, callback)
    },

    call(name, ...params) {
        this.bus.call(name, ...params)
    },
}
