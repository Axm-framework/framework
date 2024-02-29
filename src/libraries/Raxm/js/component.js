import Message, { PrefetchMessage } from "./message.js";
import { MessageBus, debounce, dataGet, dispatch, walk } from "./util/utils.js";
import { getDirectives, PREFIX_STRING, PREFIX_DISPLAY } from "./directives.js";
import { trigger } from "./events.js";
import DOM from "./dom/dom.js";
import nodeInitializer from "./node_initializer.js";
import store from "./store.js";
import PrefetchManager from "./component/PrefetchManager.js";
import { generateRaxmObject } from "./$raxm.js";

export default class Component {
    constructor(el, connection) {
        if (el.__raxm) throw "Component already initialized";

        el.__raxm = this;

        this.el = el;

        this.lastFreshHtml = this.el.outerHTML;

        this.id = this.el.getAttribute(`${PREFIX_DISPLAY}id`);

        this.checkForMultipleRootElements();

        this.connection = connection;

        this.encodeIData = this.el.getAttribute(
            `${PREFIX_DISPLAY}initial-data`
        );
        const initialData = JSON.parse(this.encodeIData);

        if (!initialData) {
            throw (
                new `Initial data missing on Axm component with id: `() +
                this.id
            );
        }

        // this.el.removeAttribute(`${PREFIX_DISPLAY}initial-data`)

        this.fingerprint = initialData.fingerprint;
        this.serverMemo = initialData.serverMemo;
        this.effects = initialData.effects;

        this.listeners = this.effects.listeners;
        this.updateQueue = [];
        this.deferredActions = {};
        this.tearDownCallbacks = [];
        this.messageInTransit = undefined;
        this.scopedListeners = new MessageBus();
        this.prefetchManager = new PrefetchManager(this);
        this.watchers = {};
        this.genericLoadingEls = {};

        store.callHook("component.initialized", this);

        this.$raxm = generateRaxmObject(this, this.serverMemo);

        this.initialize();
    }

    get name() {
        return this.fingerprint.name;
    }

    get data() {
        return this.serverMemo.data;
    }

    get childIds() {
        return Object.values(this.serverMemo.children).map((child) => child.id);
    }

    checkForMultipleRootElements() {
        // Count the number of elements between the first element in the component and the
        // injected "component-end" marker. This is an HTML comment with notation.
        let countElementsBeforeMarker = (el, carryCount = 0) => {
            if (!el) return carryCount;

            // If we see the "end" marker, we can return the number of elements in between we've seen.
            if (
                el.nodeType === Node.COMMENT_NODE &&
                el.textContent.includes(`${PREFIX_STRING}-end:${this.id}`)
            )
                return carryCount;

            let newlyDiscoveredEls = el.nodeType === Node.ELEMENT_NODE ? 1 : 0;

            return countElementsBeforeMarker(
                el.nextSibling,
                carryCount + newlyDiscoveredEls
            );
        };

        if (countElementsBeforeMarker(this.el.nextSibling) > 0) {
            console.warn(
                `Raxm: Multiple root elements detected. This is not supported.`,
                this.el
            );
        }
    }

    initialize() {
        this.walk(
            // Will run for every node in the component tree (not child component nodes).
            (el) => nodeInitializer.initialize(el, this),
            // When new component is encountered in the tree, add it.
            (el) => store.addComponent(new Component(el, this.connection))
        );
    }

    getPropertyValueIncludingDefers(name) {
        let action = this.deferredActions[name];

        if (!action) return this.get(name);

        return action.payload.value;
    }

    updateServerMemoFromResponseAndMergeBackIntoResponse(message) {
        // We have to do a fair amount of object merging here, but we can't use expressive syntax like {...}
        // because browsers mess with the object key order which will break Raxm request checksum checks.

        Object.entries(message.response.serverMemo).forEach(([key, value]) => {
            // Because "data" is "partial" from the server, we have to deep merge it.
            if (key === "data") {
                Object.entries(value || {}).forEach(([dataKey, dataValue]) => {
                    this.serverMemo.data[dataKey] = dataValue;

                    if (message.shouldSkipWatcherForDataKey(dataKey)) return;

                    // Because Raxm (for payload reduction purposes) only returns the data that has changed,
                    // we can use all the data keys from the response as watcher triggers.
                    Object.entries(this.watchers).forEach(([key, watchers]) => {
                        let originalSplitKey = key.split(".");
                        let basePropertyName = originalSplitKey.shift();
                        let restOfPropertyName = originalSplitKey.join(".");

                        if (basePropertyName == dataKey) {
                            // If the key deals with nested data, use the "get" function to get
                            // the most nested data. Otherwise, return the entire data chunk.
                            let potentiallyNestedValue = !!restOfPropertyName
                                ? dataGet(dataValue, restOfPropertyName)
                                : dataValue;

                            watchers.forEach((watcher) =>
                                watcher(potentiallyNestedValue)
                            );
                        }
                    });
                });
            } else {
                // Every other key, we can just overwrite.
                this.serverMemo[key] = value;
            }
        });

        // Merge back serverMemo changes so the response data is no longer incomplete.
        message.response.serverMemo = Object.assign({}, this.serverMemo);
    }

    //mioooo
    incribeInitialDataOnElement() {
        let el = this.el;
        el.setAttribute(`${PREFIX_DISPLAY}initial-data`, this.encodeIData);
    }

    watch(name, callback) {
        if (!this.watchers[name]) this.watchers[name] = [];

        this.watchers[name].push(callback);
    }

    on(event, callback) {
        this.scopedListeners.register(event, callback);
    }

    fireMessage() {
        if (this.messageInTransit) return;

        Object.entries(this.deferredActions).forEach(([modelName, action]) => {
            this.updateQueue.unshift(action);
        });

        this.deferredActions = {};

        this.messageInTransit = new Message(this, this.updateQueue);
        let sendMessage = () => {
            this.connection.sendMessage(this.messageInTransit);

            store.callHook("message.sent", this.messageInTransit, this);

            this.updateQueue = [];
        };

        if (window.capturedRequestsForDusk) {
            window.capturedRequestsForDusk.push(sendMessage);
        } else {
            sendMessage();
        }
    }

    messageSendFailed() {
        if (!this.messageInTransit) return;
        store.callHook("message.failed", this.messageInTransit, this);

        this.messageInTransit.reject();

        this.messageInTransit = null;
    }

    receiveMessage(message, payload) {
        message.storeResponse(payload);

        if (message instanceof PrefetchMessage) return;

        this.handleResponse(message);

        // This bit of logic ensures that if actions were queued while a request was
        // out to the server, they are sent when the request comes back.
        if (this.updateQueue.length > 0) {
            this.fireMessage();
        }

        dispatch(document, "Raxm:update");
    }

    handleResponse(message) {
        let response = message.response;

        this.updateServerMemoFromResponseAndMergeBackIntoResponse(message);

        store.callHook("message.received", message, this);

        if (response.effects.html) {
            // If we get HTML from the server, store it for the next time we might not.
            this.lastFreshHtml = response.effects.html;

            this.handleMorph(response.effects.html.trim());
        } else {
            // It's important to still "morphdom" even when the server HTML hasn't changed,
            // because Alpine needs to be given the chance to update.
            this.handleMorph(this.lastFreshHtml);
        }

        if (response.effects.dirty) {
            this.forceRefreshDataBoundElementsMarkedAsDirty(
                response.effects.dirty
            );
        }

        if (!message.replaying) {
            this.messageInTransit && this.messageInTransit.resolve();

            this.messageInTransit = null;

            if (response.effects.emits && response.effects.emits.length > 0) {
                response.effects.emits.forEach((event) => {
                    this.scopedListeners.call(event.event, ...event.params);

                    if (event.selfOnly) {
                        store.emitSelf(this.id, event.event, ...event.params);
                    } else if (event.to) {
                        store.emitTo(event.to, event.event, ...event.params);
                    } else if (event.ancestorsOnly) {
                        store.emitUp(this.el, event.event, ...event.params);
                    } else {
                        store.emit(event.event, ...event.params);
                    }
                });
            }

            if (
                response.effects.dispatches &&
                response.effects.dispatches.length > 0
            ) {
                response.effects.dispatches.forEach((event) => {
                    const data = event.data ? event.data : {};
                    const e = new CustomEvent(event.event, {
                        bubbles: true,
                        detail: data,
                    });
                    this.el.dispatchEvent(e);
                });
            }
        }

        store.callHook("message.processed", message, this);

        // This means "$this->redirect()" was called in the component. let's just bail and redirect.
        if (response.effects.redirect) {
            setTimeout(() => this.redirect(response.effects.redirect));

            return;
        }
    }

    redirect(url) {
        window.location.href = url;
    }

    forceRefreshDataBoundElementsMarkedAsDirty(dirtyInputs) {
        this.walk((el) => {
            let directives = getDirectives(el);
            if (directives.missing("model")) return;

            const modelValue = directives.get("model").value;

            if (
                !(el.nodeName == "SELECT" && !el.multiple) &&
                DOM.hasFocus(el) &&
                !dirtyInputs.includes(modelValue)
            )
                return;

            DOM.setInputValueFromModel(el, this);
        });
    }

    handleMorph(dom) {
        trigger("effects", this.el, dom);
    }

    walk(callback, callbackWhenNewComponentIsEncountered = (el) => {}) {
        walk(this.el, (el) => {
            // Skip the root component element.
            if (el.isSameNode(this.el)) {
                callback(el);
                return;
            }

            // If we encounter a nested component, skip walking that tree.
            if (el.hasAttribute(`${PREFIX_DISPLAY}id`)) {
                callbackWhenNewComponentIsEncountered(el);

                return false;
            }

            if (callback(el) === false) {
                return false;
            }
        });
    }

    addListenerForTeardown(teardownCallback) {
        this.tearDownCallbacks.push(teardownCallback);
    }

    tearDown() {
        this.tearDownCallbacks.forEach((callback) => callback());
    }
}
