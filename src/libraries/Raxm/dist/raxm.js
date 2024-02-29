(() => {
  // js/action/index.js
  var action_default = class {
    constructor(el, skipWatcher2 = false) {
      this.el = el;
      this.skipWatcher = skipWatcher2;
      this.resolveCallback = () => {
      };
      this.rejectCallback = () => {
      };
      this.signature = (Math.random() + 1).toString(36).substring(8);
    }
    toId() {
      return btoa(encodeURIComponent(this.el.outerHTML));
    }
    onResolve(callback) {
      this.resolveCallback = callback;
    }
    onReject(callback) {
      this.rejectCallback = callback;
    }
    resolve(thing) {
      this.resolveCallback(thing);
    }
    reject(thing) {
      this.rejectCallback(thing);
    }
  };

  // js/action/event.js
  var event_default = class extends action_default {
    constructor(event, params, el) {
      super(el);
      this.type = "fireEvent";
      this.payload = {
        id: this.signature,
        event,
        params
      };
    }
    toId() {
      return btoa(encodeURIComponent(this.type, this.payload.event, JSON.stringify(this.payload.params)));
    }
  };

  // js/util/utils.js
  function kebabCase(subject) {
    return subject.replace(/([a-z])([A-Z])/g, "$1-$2").replace(/[_\s]/, "-").toLowerCase();
  }
  var WeakBag = class {
    constructor() {
      this.arrays = /* @__PURE__ */ new WeakMap();
    }
    add(key2, value2) {
      if (!this.arrays.has(key2))
        this.arrays.set(key2, []);
      this.arrays.get(key2).push(value2);
    }
    get(key2) {
      return this.arrays.has(key2) ? this.arrays.get(key2) : [];
    }
    each(key2, callback) {
      return this.get(key2).forEach(callback);
    }
  };
  var MessageBus = class {
    constructor() {
      this.listeners = {};
    }
    register(name, callback) {
      if (!this.listeners[name]) {
        this.listeners[name] = [];
      }
      this.listeners[name].push(callback);
    }
    call(name, ...params) {
      (this.listeners[name] || []).forEach((callback) => {
        callback(...params);
      });
    }
    has(name) {
      return Object.keys(this.listeners).includes(name);
    }
  };
  function debounce(func, wait, immediate) {
    var timeout2;
    return function() {
      var context = this, args = arguments;
      var later = function() {
        timeout2 = null;
        if (!immediate)
          func.apply(context, args);
      };
      var callNow = immediate && !timeout2;
      clearTimeout(timeout2);
      timeout2 = setTimeout(later, wait);
      if (callNow)
        func.apply(context, args);
    };
  }
  function dispatch(eventName) {
    const event = document.createEvent("Events");
    event.initEvent(eventName, true, true);
    document.dispatchEvent(event);
    return event;
  }
  function walk(root, callback) {
    const visited = /* @__PURE__ */ new Set();
    const stack = [root];
    while (stack.length) {
      const node = stack.pop();
      if (visited.has(node))
        continue;
      visited.add(node);
      if (callback(node) === false)
        return;
      stack.push(...node.children);
    }
  }
  function isFunction(subject) {
    return typeof subject === "function";
  }
  function dataGet(object, key2) {
    if (key2 === "")
      return object;
    return key2.split(".").reduce((carry, i) => {
      if (carry === void 0)
        return void 0;
      return carry[i];
    }, object);
  }
  function call(method, params, context = window) {
    try {
      if (typeof method !== "string")
        throw new Error("The method provided is not a string");
      if (typeof context !== "object")
        throw new Error("The context provided is not an object");
      const func = context[method];
      if (typeof func === "function") {
        return func.call(context, ...params);
      } else {
        console.error(`The function '${method}' does not exist in the provided context.`);
      }
    } catch (error) {
      console.error(`An error occurred when calling the function '${method}':`, error);
    }
  }
  function getCsrfToken() {
    if (document.querySelector('meta[name="csrf-token"]')) {
      return document.querySelector('meta[name="csrf-token"]').getAttribute("content");
    }
    if (document.querySelector("[data-csrf]")) {
      return document.querySelector("[data-csrf]").getAttribute("data-csrf");
    }
    if (window.raxmScriptConfig["csrf"] ?? false) {
      return window.raxmScriptConfig["csrf"];
    }
    throw "Raxm: No CSRF token detected";
  }
  function contentIsFromDump(content2) {
    return !!content2.match(/<script>Sfdump\(".+"\)<\/script>/);
  }
  function splitDumpFromContent(content2) {
    let dump2 = content2.match(/.*<script>Sfdump\(".+"\)<\/script>/s);
    return [dump2, content2.replace(dump2, "")];
  }

  // js/HookManager.js
  var HookManager_default = {
    availableHooks: [
      "component.initialized",
      "directive.initialized",
      "element.initialized",
      "element.updating",
      "element.updated",
      "element.removed",
      "message.sent",
      "message.failed",
      "message.received",
      "message.processed",
      "interceptRaxmModelSetValue",
      "interceptRaxmModelAttachListener",
      "beforeReplaceState",
      "beforePushState"
    ],
    bus: new MessageBus(),
    register(name, callback) {
      if (!this.availableHooks.includes(name)) {
        throw `Raxm: Referencing unknown hook: [${name}]`;
      }
      this.bus.register(name, callback);
    },
    call(name, ...params) {
      this.bus.call(name, ...params);
    }
  };

  // js/store.js
  var store = {
    componentsById: {},
    listeners: new MessageBus(),
    initialRenderIsFinished: false,
    raxmIsInBackground: false,
    raxmIsOffline: false,
    sessionHasExpired: false,
    sessionHasExpiredCallback: void 0,
    hooks: HookManager_default,
    onErrorCallback: () => {
    },
    components() {
      return Object.keys(this.componentsById).map((key2) => {
        return this.componentsById[key2];
      });
    },
    addComponent(component) {
      return this.componentsById[component.id] = component;
    },
    findComponent(id) {
      return this.componentsById[id];
    },
    getComponentsByName(name) {
      return this.components().filter((component) => {
        return component.name === name;
      });
    },
    hasComponent(id) {
      return !!this.componentsById[id];
    },
    tearDownComponents() {
      this.components().forEach((component) => {
        this.removeComponent(component);
      });
    },
    on(event, callback) {
      this.listeners.register(event, callback);
    },
    emit(event, ...params) {
      this.listeners.call(event, ...params);
      this.componentsListeningForEvent(event).forEach(
        (component) => component.addAction(new event_default(event, params))
      );
    },
    emitUp(el, event, ...params) {
      this.componentsListeningForEventThatAreTreeAncestors(
        el,
        event
      ).forEach(
        (component) => component.addAction(new event_default(event, params))
      );
    },
    emitSelf(componentId, event, ...params) {
      let component = this.findComponent(componentId);
      if (component.listeners.includes(event)) {
        component.addAction(new event_default(event, params));
      }
    },
    emitTo(componentName, event, ...params) {
      let components = this.getComponentsByName(componentName);
      components.forEach((component) => {
        if (component.listeners.includes(event)) {
          component.addAction(new event_default(event, params));
        }
      });
    },
    componentsListeningForEventThatAreTreeAncestors(el, event) {
      var parentIds = [];
      var parent = el.parentElement.closest(`[${PREFIX_REGEX}id]`);
      while (parent) {
        parentIds.push(parent.getAttribute(`${PREFIX_REGEX}id`));
        parent = parent.parentElement.closest(`[${PREFIX_REGEX}id]`);
      }
      return this.components().filter((component) => {
        return component.listeners.includes(event) && parentIds.includes(component.id);
      });
    },
    componentsListeningForEvent(event) {
      return this.components().filter((component) => {
        return component.listeners.includes(event);
      });
    },
    registerHook(name, callback) {
      this.hooks.register(name, callback);
    },
    callHook(name, ...params) {
      this.hooks.call(name, ...params);
    },
    changeComponentId(component, newId) {
      let oldId = component.id;
      component.id = newId;
      component.fingerprint.id = newId;
      this.componentsById[newId] = component;
      delete this.componentsById[oldId];
      this.components().forEach((component2) => {
        let children = component2.serverMemo.children || {};
        Object.entries(children).forEach(([key2, { id, tagName }]) => {
          if (id === oldId) {
            children[key2].id = newId;
          }
        });
      });
    },
    removeComponent(component) {
      component.tearDown();
      delete this.componentsById[component.id];
    },
    onError(callback) {
      this.onErrorCallback = callback;
    },
    getClosestParentId(childId, subsetOfParentIds) {
      let distancesByParentId = {};
      subsetOfParentIds.forEach((parentId) => {
        let distance = this.getDistanceToChild(parentId, childId);
        if (distance)
          distancesByParentId[parentId] = distance;
      });
      let smallestDistance = Math.min(...Object.values(distancesByParentId));
      let closestParentId;
      Object.entries(distancesByParentId).forEach(([parentId, distance]) => {
        if (distance === smallestDistance)
          closestParentId = parentId;
      });
      return closestParentId;
    },
    closestComponent(el, strict = true) {
      let currentElement = el;
      while (currentElement) {
        if (currentElement.__raxm) {
          return currentElement.__raxm;
        }
        currentElement = currentElement.parentElement;
      }
      if (strict) {
        throw new Error("Could not find Raxm component in DOM tree");
      }
    },
    getDistanceToChild(parentId, childId, distanceMemo = 1) {
      let parentComponent = this.findComponent(parentId);
      if (!parentComponent)
        return;
      let childIds = parentComponent.childIds;
      if (childIds.includes(childId))
        return distanceMemo;
      for (let i = 0; i < childIds.length; i++) {
        let distance = this.getDistanceToChild(childIds[i], childId, distanceMemo + 1);
        if (distance)
          return distance;
      }
    }
  };
  var store_default = store;
  function find(id) {
    return store.findComponent(id);
  }
  function first() {
    return store.components()[0];
  }
  function getByName(name) {
    return store.getComponentsByName(name).map((i) => i.$raxm);
  }
  function all() {
    return store.components();
  }
  function on(event, callback) {
    return store.on(event, callback);
  }
  function hook(name, callback) {
    return store.registerHook(name, callback);
  }
  function trigger(name, params) {
    return store.callHook(name, ...params);
  }
  function closestComponent2(el, strict = true) {
    return store.closestComponent(el, strict);
  }

  // js/directives.js
  var PREFIX_STRING = "axm";
  var PREFIX_REGEX = PREFIX_STRING + "\\:";
  var PREFIX_DISPLAY = PREFIX_STRING + ":";
  function matchesForRaxmDirective(attributeName) {
    return attributeName.match(new RegExp(PREFIX_REGEX));
  }
  function extractDirective(el, name) {
    let [type, ...modifiers] = name.replace(new RegExp(PREFIX_REGEX), "").split(".");
    return new Directive(type, modifiers, name, el);
  }
  function directive(name, callback) {
    store_default.registerHook("directive.initialized", ({ el, component, directive: directive2, cleanup: cleanup2 }) => {
      if (directive2.type === name) {
        callback({
          el,
          directive: directive2,
          component,
          cleanup: cleanup2
        });
      }
    });
  }
  function getDirectives(el) {
    return new DirectiveManager(el);
  }
  var DirectiveManager = class {
    constructor(el) {
      this.el = el;
      this.directives = this.extractTypeModifiersAndValue();
    }
    all() {
      return this.directives;
    }
    has(type) {
      return this.directives.map((directive2) => directive2.type).includes(type);
    }
    missing(type) {
      return !this.has(type);
    }
    get(type) {
      return this.directives.find((directive2) => directive2.type === type);
    }
    extractTypeModifiersAndValue() {
      return Array.from(this.el.getAttributeNames().filter((name) => matchesForRaxmDirective(name)).map((name) => extractDirective(this.el, name)));
    }
  };
  var Directive = class {
    constructor(type, modifiers, rawName, el) {
      this.rawName = this.raw = rawName;
      this.el = el;
      this.eventContext;
      this.type = type;
      this.modifiers = modifiers;
      this.expression = this.el.getAttribute(this.rawName);
    }
    setEventContext(context) {
      this.eventContext = context;
    }
    get value() {
      return this.el.getAttribute(this.rawName);
    }
    get method() {
      const { method } = this.parseOutMethodAndParams(this.value);
      return method;
    }
    get params() {
      const { params } = this.parseOutMethodAndParams(this.value);
      return params;
    }
    parseOutMethodAndParams(rawMethod) {
      let method = rawMethod;
      let params = [];
      const methodAndParamString = method.match(/(.*?)\((.*)\)/s);
      if (methodAndParamString) {
        method = methodAndParamString[1];
        let func = new Function("$event", `return (function () {
                for (var l=arguments.length, p=new Array(l), k=0; k<l; k++) {
                    p[k] = arguments[k];
                }
                return [].concat(p);
            })(${methodAndParamString[2]})`);
        params = func(this.eventContext);
      }
      return { method, params };
    }
    durationOr(defaultDuration) {
      let durationInMilliSeconds;
      const durationInMilliSecondsString = this.modifiers.find((mod) => mod.match(/([0-9]+)ms/));
      const durationInSecondsString = this.modifiers.find((mod) => mod.match(/([0-9]+)s/));
      if (durationInMilliSecondsString) {
        durationInMilliSeconds = Number(durationInMilliSecondsString.replace("ms", ""));
      } else if (durationInSecondsString) {
        durationInMilliSeconds = Number(durationInSecondsString.replace("s", "")) * 1e3;
      }
      return durationInMilliSeconds || defaultDuration;
    }
  };

  // js/dom/dom.js
  var dom_default = {
    rootComponentElements() {
      return Array.from(document.querySelectorAll(`[${PREFIX_REGEX}id]`));
    },
    rootComponentElementsWithNoParents(node = null) {
      if (node === null)
        node = document;
      const els = node.querySelectorAll(`[${PREFIX_REGEX}initial-data]:not([${PREFIX_REGEX}initial-data] > [${PREFIX_REGEX}initial-data])`);
      return Array.from(els);
    },
    allModelElementsInside(root) {
      return Array.from(root.querySelectorAll(`[${PREFIX_REGEX}model]`));
    },
    getByAttributeAndValue(attribute, value2) {
      return document.querySelector(`[${PREFIX_REGEX}${attribute}="${value2}"]`);
    },
    nextFrame(fn) {
      requestAnimationFrame(() => {
        requestAnimationFrame(fn.bind(this));
      });
    },
    closestRoot(el) {
      return this.closestByAttribute(el, "id");
    },
    closestByAttribute(el, attribute) {
      const closestEl = el.closest(`[${PREFIX_REGEX}${attribute}]`);
      if (!closestEl) {
        throw `
                Raxm Error:

                Cannot find parent element in DOM tree containing attribute: [${PREFIX_REGEX}${attribute}].

                Usually this is caused by Raxm's DOM-differ not being able to properly track changes.

                Reference the following guide for common causes: https://axm-raxm.com/docs/troubleshooting 

                Referenced element:

            ${el.outerHTML}`;
      }
      return closestEl;
    },
    isComponentRootEl(el) {
      return this.hasAttribute(el, "id");
    },
    hasAttribute(el, attribute) {
      return el.hasAttribute(`${PREFIX_REGEX}${attribute}`);
    },
    getAttribute(el, attribute) {
      return el.getAttribute(`${PREFIX_REGEX}${attribute}`);
    },
    removeAttribute(el, attribute) {
      return el.removeAttribute(`${PREFIX_REGEX}${attribute}`);
    },
    setAttribute(el, attribute, value2) {
      return el.setAttribute(`${PREFIX_REGEX}${attribute}`, value2);
    },
    hasFocus(el) {
      return el === document.activeElement;
    },
    isInput(el) {
      return ["INPUT", "TEXTAREA", "SELECT"].includes(
        el.tagName.toUpperCase()
      );
    },
    isTextInput(el) {
      return ["INPUT", "TEXTAREA"].includes(el.tagName.toUpperCase()) && !["checkbox", "radio"].includes(el.type);
    },
    valueFromInput(el, component) {
      if (el.type === "checkbox") {
        let modelName = getDirectives(el).get("model").value;
        let modelValue = component.deferredActions[modelName] ? component.deferredActions[modelName].payload.value : dataGet(component.data, modelName);
        if (Array.isArray(modelValue)) {
          return this.mergeCheckboxValueIntoArray(el, modelValue);
        }
        if (el.checked) {
          return el.getAttribute("value") || true;
        } else {
          return false;
        }
      } else if (el.tagName.toLowerCase() === "select" && el.multiple) {
        return this.getSelectValues(el);
      }
      return el.value;
    },
    mergeCheckboxValueIntoArray(el, arrayValue) {
      if (el.checked) {
        return arrayValue.includes(el.value) ? arrayValue : arrayValue.concat(el.value);
      }
      return arrayValue.filter((item) => item != el.value);
    },
    setInputValueFromModel(el, component) {
      const modelString = getDirectives(el).get("model").value;
      const modelValue = dataGet(component.data, modelString);
      if (el.tagName.toLowerCase() === "input" && el.type === "file")
        return;
      this.setInputValue(el, modelValue);
    },
    setInputValue(el, value2) {
      store_default.callHook("interceptRaxmModelSetValue", value2, el);
      if (el.type === "radio") {
        el.checked = el.value == value2;
      } else if (el.type === "checkbox") {
        if (Array.isArray(value2)) {
          let valueFound = false;
          value2.forEach((val) => {
            if (val == el.value) {
              valueFound = true;
            }
          });
          el.checked = valueFound;
        } else {
          el.checked = !!value2;
        }
      } else if (el.tagName.toLowerCase() === "select") {
        this.updateSelect(el, value2);
      } else {
        value2 = value2 === void 0 ? "" : value2;
        el.value = value2;
      }
    },
    getSelectValues(el) {
      return Array.from(el.options).filter((option) => option.selected).map((option) => {
        return option.value || option.text;
      });
    },
    updateSelect(el, value2) {
      const arrayWrappedValue = [].concat(value2).map((value3) => {
        return value3 + "";
      });
      Array.from(el.options).forEach((option) => {
        option.selected = arrayWrappedValue.includes(option.value);
      });
    },
    isAsset(el) {
      const assetTags = ["link", "style", "script"];
      return assetTags.includes(el.tagName.toLowerCase());
    },
    isScript(el) {
      return el.tagName.toLowerCase() === "script";
    },
    cloneScriptTag(el) {
      let script = document.createElement("script");
      script.textContent = el.textContent;
      script.async = el.async;
      for (let attr of el.attributes) {
        script.setAttribute(attr.name, attr.value);
      }
      return script;
    },
    ignoreAttributes(subject, attributesToRemove) {
      let result = subject;
      attributesToRemove.forEach((attr) => {
        const regex = new RegExp(`${attr}="[^"]*"|${attr}='[^']*'`, "g");
        result = result.replace(regex, "");
      });
      return result.trim();
    }
  };

  // js/message.js
  var Message = class {
    constructor(component, updateQueue) {
      this.component = component;
      this.updateQueue = updateQueue;
    }
    payload() {
      return {
        fingerprint: this.component.fingerprint,
        serverMemo: this.component.serverMemo,
        updates: this.updateQueue.map((update) => ({
          type: update.type,
          payload: update.payload
        }))
      };
    }
    shouldSkipWatcherForDataKey(dataKey) {
      if (this.response.effects.dirty.includes(dataKey))
        return false;
      let compareBeforeFirstDot = (subject, value2) => {
        if (typeof subject !== "string" || typeof value2 !== "string")
          return false;
        return subject.split(".")[0] === value2.split(".")[0];
      };
      return this.updateQueue.filter((update) => compareBeforeFirstDot(update.name, dataKey)).some((update) => update.skipWatcher);
    }
    storeResponse(payload) {
      return this.response = payload;
    }
    resolve() {
      let returns = this.response.effects.returns || [];
      this.updateQueue.forEach((update) => {
        if (update.type !== "callMethod")
          return;
        update.resolve(
          returns[update.signature] !== void 0 ? returns[update.signature] : returns[update.method] !== void 0 ? returns[update.method] : null
        );
      });
    }
    reject() {
      this.updateQueue.forEach((update) => {
        update.reject();
      });
    }
  };
  var PrefetchMessage = class extends Message {
    constructor(component, action) {
      super(component, [action]);
    }
    get prefetchId() {
      return this.updateQueue[0].toId();
    }
  };

  // js/events.js
  var listeners = [];
  function on2(events, callback) {
    if (typeof events === "string") {
      events = [events];
    }
    events.forEach((eventName) => {
      if (!listeners[eventName]) {
        listeners[eventName] = [];
      }
      listeners[eventName].push(callback);
    });
    return () => {
      events.forEach((eventName) => {
        if (listeners[eventName]) {
          listeners[eventName] = listeners[eventName].filter((listener) => listener !== callback);
        }
      });
    };
  }
  function trigger2(name, ...params) {
    let callbacks = listeners[name] || [];
    let finishers = [];
    for (let i = 0; i < callbacks.length; i++) {
      let finisher = callbacks[i](...params);
      if (isFunction(finisher))
        finishers.push(finisher);
    }
    return (result) => {
      let latest = result;
      for (let i = 0; i < finishers.length; i++) {
        let iResult = finishers[i](latest);
        if (iResult !== void 0) {
          latest = iResult;
        }
      }
      return latest;
    };
  }

  // js/action/method.js
  var method_default = class extends action_default {
    constructor(method, params, el, skipWatcher2 = false) {
      super(el, skipWatcher2);
      this.type = "callMethod";
      this.method = method;
      this.payload = {
        id: this.signature,
        method,
        params
      };
    }
  };

  // js/action/model.js
  var model_default = class extends action_default {
    constructor(name, value2, el) {
      super(el);
      this.type = "syncInput";
      this.name = name;
      this.payload = {
        id: this.signature,
        name,
        value: value2
      };
    }
  };

  // js/action/deferred-model.js
  var deferred_model_default = class extends action_default {
    constructor(name, value2, el, skipWatcher2 = false) {
      super(el, skipWatcher2);
      this.type = "syncInput";
      this.name = name;
      this.payload = {
        id: this.signature,
        name,
        value: value2
      };
    }
  };

  // js/commit.js
  async function addMethodAction(component, method, ...params) {
    return new Promise((resolve, reject) => {
      let action = new method_default(method, params, component.el);
      addAction(component, action);
      action.onResolve((thing) => resolve(thing));
      action.onReject((thing) => reject(thing));
    });
  }
  function addAction(component, action) {
    if (action instanceof deferred_model_default) {
      component.deferredActions[action.name] = action;
      return;
    }
    if (component.prefetchManager.actionHasPrefetch(action) && component.prefetchManager.actionPrefetchResponseHasBeenReceived(action)) {
      const message = component.prefetchManager.getPrefetchMessageByAction(action);
      component.handleResponse(message);
      component.prefetchManager.clearPrefetches();
      return;
    }
    component.updateQueue.push(action);
    debounce(component.fireMessage, 5).apply(component);
    component.prefetchManager.clearPrefetches();
  }
  function get(component, name) {
    return name.split(".").reduce((carry, segment) => typeof carry === "undefined" ? carry : carry[segment], component.data);
  }
  async function set(component, name, value2, defer2 = false, skipWatcher2 = false) {
    if (defer2) {
      addAction(component, new deferred_model_default(name, value2, component.el, skipWatcher2));
    } else {
      addAction(component, new method_default("$set", [name, value2], component.el, skipWatcher2));
    }
  }
  function modelSyncDebounce(callback, time) {
    let modelDebounceCallbacks = [];
    let callbackRegister = { callback: () => {
    } };
    modelDebounceCallbacks.push(callbackRegister);
    let timeout2;
    return (e) => {
      clearTimeout(timeout2);
      timeout2 = setTimeout(() => {
        callback(e);
        timeout2 = void 0;
        callbackRegister.callback();
      }, time);
      callbackRegister.callback = () => clearTimeout(timeout2);
    };
  }
  function callAfterModelDebounce(callback, modelDebounceCallbacks) {
    if (modelDebounceCallbacks) {
      modelDebounceCallbacks.forEach((callbackRegister) => {
        callbackRegister.callback();
      });
    }
    callback();
  }
  function addPrefetchAction(component, action) {
    if (component.prefetchManager.actionHasPrefetch(action))
      return;
    const message = new PrefetchMessage(component, action);
    component.prefetchManager.addMessage(message);
    component.connection.sendMessage(message);
  }

  // js/node_initializer.js
  var node_initializer_default = {
    initialize(el, component) {
      getDirectives(el).all().forEach((directive2) => {
        store_default.callHook("directive.initialized", { el, component, directive: directive2, cleanup: () => {
        } });
        this.attachDomListener(el, directive2, component);
      });
      store_default.callHook("element.initialized", el, component);
    },
    attachDomListener(el, directive2, component) {
      switch (directive2.type) {
        case "keydown":
        case "keyup":
          this.attachListener(el, directive2, component, (e) => {
            const systemKeyModifiers = [
              "ctrl",
              "shift",
              "alt",
              "meta",
              "cmd",
              "super"
            ];
            const selectedSystemKeyModifiers = systemKeyModifiers.filter(
              (key2) => directive2.modifiers.includes(key2)
            );
            if (selectedSystemKeyModifiers.length > 0) {
              const selectedButNotPressedKeyModifiers = selectedSystemKeyModifiers.filter(
                (key2) => {
                  if (key2 === "cmd" || key2 === "super")
                    key2 = "meta";
                  return !e[`${key2}Key`];
                }
              );
              if (selectedButNotPressedKeyModifiers.length > 0)
                return false;
            }
            if (e.keyCode === 32 || (e.key === " " || e.key === "Spacebar")) {
              return directive2.modifiers.includes("space");
            }
            let modifiers = directive2.modifiers.filter((modifier) => {
              return !modifier.match(/^debounce$/) && !modifier.match(/^[0-9]+m?s$/);
            });
            return Boolean(modifiers.length === 0 || e.key && modifiers.includes(kebabCase(e.key)));
          });
          break;
        case "click":
          this.attachListener(el, directive2, component, (e) => {
            if (!directive2.modifiers.includes("self"))
              return;
            return el.isSameNode(e.target);
          });
          break;
        default:
          this.attachListener(el, directive2, component);
          break;
      }
    },
    attachListener(el, directive2, component, callback) {
      if (directive2.modifiers.includes("prefetch")) {
        el.addEventListener("mouseenter", () => {
          addPrefetchAction(component, directive2.method, directive2.params);
        });
      }
      const event = directive2.type;
      const handler = (e) => {
        if (callback && callback(e) === false) {
          return;
        }
        if (directive2.modifiers.includes("front")) {
          const { method, params } = directive2;
          return call(method, params);
        }
        callAfterModelDebounce(() => {
          const el2 = e.target;
          directive2.setEventContext(e);
          this.preventAndStop(e, directive2.modifiers);
          const method = directive2.method;
          let params = directive2.params;
          if (params.length === 0 && e instanceof CustomEvent && e.detail) {
            params.push(e.detail);
          }
          if (method === "$emit") {
            component.scopedListeners.call(...params);
            store_default.emit(...params);
            return;
          }
          if (method === "$emitUp") {
            store_default.emitUp(el2, ...params);
            return;
          }
          if (method === "$emitSelf") {
            store_default.emitSelf(component.id, ...params);
            return;
          }
          if (method === "$emitTo") {
            store_default.emitTo(...params);
            return;
          }
          if (directive2.value) {
            addMethodAction(component, method, params);
          }
        });
      };
      const debounceIf = (condition, callback2, time) => {
        return condition ? debounce(callback2, time) : callback2;
      };
      const hasDebounceModifier = directive2.modifiers.includes("debounce");
      const debouncedHandler = debounceIf(
        hasDebounceModifier,
        handler,
        directive2.durationOr(150)
      );
      el.addEventListener(event, debouncedHandler);
      component.addListenerForTeardown(() => {
        el.removeEventListener(event, debouncedHandler);
      });
    },
    preventAndStop(event, modifiers) {
      modifiers.includes("prevent") && event.preventDefault();
      modifiers.includes("stop") && event.stopPropagation();
    }
  };

  // js/component/PrefetchManager.js
  var PrefetchManager = class {
    constructor(component) {
      this.component = component;
      this.prefetchMessagesByActionId = {};
    }
    addMessage(message) {
      this.prefetchMessagesByActionId[message.prefetchId] = message;
    }
    actionHasPrefetch(action) {
      return Object.keys(this.prefetchMessagesByActionId).includes(action.toId());
    }
    actionPrefetchResponseHasBeenReceived(action) {
      return !!this.getPrefetchMessageByAction(action).response;
    }
    getPrefetchMessageByAction(action) {
      return this.prefetchMessagesByActionId[action.toId()];
    }
    clearPrefetches() {
      this.prefetchMessagesByActionId = {};
    }
  };
  var PrefetchManager_default = PrefetchManager;

  // js/features/supportEvents.js
  on2("effects", (component, effects) => {
    registerListeners(component, effects.listeners || []);
    dispatchEvents(component, effects.dispatches || []);
  });
  function registerListeners(component, listeners2) {
    listeners2.forEach((name) => {
      let handler = (e) => {
        if (e.__raxm)
          e.__raxm.receivedBy.push(component);
        component.$raxm.call("__dispatch", name, e.detail || {});
      };
      window.addEventListener(name, handler);
      component.addCleanup(() => window.removeEventListener(name, handler));
      component.el.addEventListener(name, (e) => {
        if (e.__raxm && e.bubbles)
          return;
        if (e.__raxm)
          e.__raxm.receivedBy.push(component.id);
        component.$raxm.call("__dispatch", name, e.detail || {});
      });
    });
  }
  function dispatchEvents(component, dispatches) {
    dispatches.forEach(({ name, params = {}, self = false, to }) => {
      if (self)
        dispatchSelf(component, name, params);
      else if (to)
        dispatchTo(component, to, name, params);
      else
        dispatch2(component, name, params);
    });
  }
  function dispatchEvent2(target, name, params, bubbles = true) {
    let e = new CustomEvent(name, { bubbles, detail: params });
    e.__raxm = { name, params, receivedBy: [] };
    target.dispatchEvent(e);
  }
  function dispatch2(component, name, params) {
    dispatchEvent2(component.el, name, params);
  }
  function dispatchSelf(component, name, params) {
    dispatchEvent2(component.el, name, params, false);
  }
  function dispatchTo(component, componentName, name, params) {
    let targets = store_default(componentName);
    targets.forEach((target) => {
      dispatchEvent2(target.el, name, params, false);
    });
  }
  function listen(component, name, callback) {
    component.el.addEventListener(name, (e) => {
      callback(e.detail);
    });
  }

  // js/features/supportEntangle.js
  function generateEntangleFunction(component) {
    return (name, live) => {
      let isLive = live;
      let raxmProperty2 = name;
      let raxmComponent = component.$wire;
      let raxmPropertyValue = raxmComponent.get(raxmProperty2);
      let interceptor = Alpine.interceptor((initialValue, getter, setter, path, key2) => {
        if (typeof raxmPropertyValue === "undefined") {
          console.error(`Raxm Entangle Error: Raxm property '${raxmProperty2}' cannot be found`);
          return;
        }
        queueMicrotask(() => {
          Alpine.entangle({
            get() {
              return raxmComponent.get(name);
            },
            set(value2) {
              raxmComponent.set(name, value2, isLive);
            }
          }, {
            get() {
              return getter();
            },
            set(value2) {
              setter(value2);
            }
          });
        });
        return raxmComponent.get(name);
      }, (obj) => {
        Object.defineProperty(obj, "live", {
          get() {
            isLive = true;
            return obj;
          }
        });
      });
      return interceptor(raxmPropertyValue);
    };
  }

  // js/features/supportFileUploads.js
  var uploadManagers = /* @__PURE__ */ new WeakMap();
  function getUploadManager(component) {
    if (!uploadManagers.has(component)) {
      let manager = new UploadManager(component);
      uploadManagers.set(component, manager);
      manager.registerListeners();
    }
    return uploadManagers.get(component);
  }
  function handleFileUpload(el, property2, component, cleanup2) {
    if (!(el.tagName === "INPUT" && el.type === "file"))
      return;
    let manager = getUploadManager(component);
    let start3 = () => el.dispatchEvent(new CustomEvent("raxm-upload-start", { bubbles: true, detail: { id: component.id, property: property2 } }));
    let finish2 = () => el.dispatchEvent(new CustomEvent("raxm-upload-finish", { bubbles: true, detail: { id: component.id, property: property2 } }));
    let error = () => el.dispatchEvent(new CustomEvent("raxm-upload-error", { bubbles: true, detail: { id: component.id, property: property2 } }));
    let progress2 = (progressEvent) => {
      var percentCompleted = Math.round(progressEvent.loaded * 100 / progressEvent.total);
      el.dispatchEvent(
        new CustomEvent("raxm-upload-progress", {
          bubbles: true,
          detail: { progress: percentCompleted }
        })
      );
    };
    let eventHandler = (e) => {
      if (e.target.files.length === 0)
        return;
      start3();
      if (e.target.multiple) {
        manager.uploadMultiple(property2, e.target.files, finish2, error, progress2);
      } else {
        manager.upload(property2, e.target.files[0], finish2, error, progress2);
      }
    };
    el.addEventListener("change", eventHandler);
    let clearFileInputValue = () => {
      el.value = null;
    };
    el.addEventListener("click", clearFileInputValue);
    cleanup2(() => {
      el.removeEventListener("change", eventHandler);
      el.removeEventListener("click", clearFileInputValue);
    });
  }
  var UploadManager = class {
    constructor(component) {
      this.component = component;
      this.uploadBag = new MessageBag();
      this.removeBag = new MessageBag();
    }
    registerListeners() {
      this.component.on("upload:generatedSignedUrl", (name, url2) => {
        setUploadLoading(this.component, name);
        this.handleSignedUrl(name, url2);
      });
      this.component.on("upload:generatedSignedUrlForS3", (name, payload) => {
        setUploadLoading(this.component, name);
        this.handleS3PreSignedUrl(name, payload);
      });
      this.component.on("upload:finished", (name, tmpFilenames) => this.markUploadFinished(name, tmpFilenames));
      this.component.on("upload:errored", (name) => this.markUploadErrored(name));
      this.component.on("upload:removed", (name, tmpFilename) => this.removeBag.shift(name).finishCallback(tmpFilename));
    }
    upload(name, file, finishCallback, errorCallback, progressCallback) {
      this.setUpload(name, {
        files: [file],
        multiple: false,
        finishCallback,
        errorCallback,
        progressCallback
      });
    }
    uploadMultiple(name, files, finishCallback, errorCallback, progressCallback) {
      this.setUpload(name, {
        files: Array.from(files),
        multiple: true,
        finishCallback,
        errorCallback,
        progressCallback
      });
    }
    removeUpload(name, tmpFilename, finishCallback) {
      this.removeBag.push(name, {
        tmpFilename,
        finishCallback
      });
      this.component.call("removeUpload", name, tmpFilename);
    }
    setUpload(name, uploadObject) {
      this.uploadBag.add(name, uploadObject);
      if (this.uploadBag.get(name).length === 1) {
        this.startUpload(name, uploadObject);
      }
    }
    handleSignedUrl(name, url2) {
      let formData = new FormData();
      Array.from(this.uploadBag.first(name).files).forEach((file) => formData.append("files[]", file, file.name));
      let headers = {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-Axm": true
      };
      let csrfToken = getCsrfToken();
      if (csrfToken)
        headers["X-CSRF-TOKEN"] = csrfToken;
      this.makeRequest(name, formData, "post", url2, headers, (response) => {
        return response.paths;
      });
    }
    handleS3PreSignedUrl(name, payload) {
      let formData = this.uploadBag.first(name).files[0];
      let headers = payload.headers;
      if ("Host" in headers)
        delete headers.Host;
      let url2 = payload.url;
      this.makeRequest(name, formData, "put", url2, headers, (response) => {
        return [payload.path];
      });
    }
    makeRequest(name, formData, method, url2, headers, retrievePaths) {
      let request = new XMLHttpRequest();
      request.open(method, url2);
      Object.entries(headers).forEach(([key2, value2]) => {
        request.setRequestHeader(key2, value2);
      });
      request.upload.addEventListener("progress", (e) => {
        e.detail = {};
        e.detail.progress = Math.round(e.loaded * 100 / e.total);
        this.uploadBag.first(name).progressCallback(e);
      });
      request.addEventListener("load", () => {
        if ((request.status + "")[0] === "2") {
          let paths = retrievePaths(request.response && JSON.parse(request.response));
          this.component.$raxm.call("finishUpload", name, paths, this.uploadBag.first(name).multiple);
          return;
        }
        let errors = null;
        if (request.status === 422) {
          errors = request.response;
        }
        this.component.$raxm.call("uploadErrored", name, errors, this.uploadBag.first(name).multiple);
      });
      request.send(formData);
    }
    startUpload(name, uploadObject) {
      let fileInfos = uploadObject.files.map((file) => {
        return { name: file.name, size: file.size, type: file.type };
      });
      this.component.$raxm.call("startUpload", name, fileInfos, uploadObject.multiple);
      setUploadLoading(this.component, name);
    }
    markUploadFinished(name, tmpFilenames) {
      unsetUploadLoading(this.component);
      let uploadObject = this.uploadBag.shift(name);
      uploadObject.finishCallback(uploadObject.multiple ? tmpFilenames : tmpFilenames[0]);
      if (this.uploadBag.get(name).length > 0)
        this.startUpload(name, this.uploadBag.last(name));
    }
    markUploadErrored(name) {
      unsetUploadLoading(this.component);
      this.uploadBag.shift(name).errorCallback();
      if (this.uploadBag.get(name).length > 0)
        this.startUpload(name, this.uploadBag.last(name));
    }
  };
  var MessageBag = class {
    constructor() {
      this.bag = {};
    }
    add(name, thing) {
      if (!this.bag[name]) {
        this.bag[name] = [];
      }
      this.bag[name].push(thing);
    }
    push(name, thing) {
      this.add(name, thing);
    }
    first(name) {
      if (!this.bag[name])
        return null;
      return this.bag[name][0];
    }
    last(name) {
      return this.bag[name].slice(-1)[0];
    }
    get(name) {
      return this.bag[name];
    }
    shift(name) {
      return this.bag[name].shift();
    }
    call(name, ...params) {
      (this.listeners[name] || []).forEach((callback) => {
        callback(...params);
      });
    }
    has(name) {
      return Object.keys(this.listeners).includes(name);
    }
  };
  function setUploadLoading() {
  }
  function unsetUploadLoading() {
  }
  function upload(component, name, file, finishCallback = () => {
  }, errorCallback = () => {
  }, progressCallback = () => {
  }) {
    let uploadManager = getUploadManager(component);
    uploadManager.upload(
      name,
      file,
      finishCallback,
      errorCallback,
      progressCallback
    );
  }
  function uploadMultiple(component, name, files, finishCallback = () => {
  }, errorCallback = () => {
  }, progressCallback = () => {
  }) {
    let uploadManager = getUploadManager(component);
    uploadManager.uploadMultiple(
      name,
      files,
      finishCallback,
      errorCallback,
      progressCallback
    );
  }
  function removeUpload(component, name, tmpFilename, finishCallback = () => {
  }, errorCallback = () => {
  }) {
    let uploadManager = getUploadManager(component);
    uploadManager.removeUpload(
      name,
      tmpFilename,
      finishCallback,
      errorCallback
    );
  }

  // js/$raxm.js
  var properties = {};
  var fallback;
  function raxmProperty(name, callback, component = null) {
    properties[name] = callback;
  }
  function raxmFallback(callback) {
    fallback = callback;
  }
  var aliases = {
    "on": "$on",
    "get": "$get",
    "set": "$set",
    "call": "$call",
    "sync": "$sync",
    "watch": "$watch",
    "upload": "$upload",
    "commit": "$commit",
    "entangle": "$entangle",
    "dispatch": "$dispatch",
    "dispatchTo": "$dispatchTo",
    "dispatchSelf": "$dispatchSelf",
    "removeUpload": "$removeUpload",
    "uploadMultiple": "$uploadMultiple"
  };
  function getProperty(component, name) {
    return properties[name](component);
  }
  function getFallback(component) {
    return fallback(component);
  }
  raxmProperty("__instance", (component) => component);
  raxmProperty("$get", (component) => (property2) => get(component, property2));
  raxmProperty("$set", (component) => {
    set(component, property, value, defer, skipWatcher);
  });
  raxmProperty("$call", (component) => async (method, ...params) => {
    return await addMethodAction(component, method, ...params);
  });
  raxmProperty("$entangle", (component) => (name) => {
    return generateEntangleFunction(component)(name);
  });
  raxmProperty("$toggle", (component) => (name) => {
    return set(component, name, !component.$raxm.get(name));
  });
  raxmProperty("$watch", (component) => (path, callback) => {
    let firstTime = true;
    let oldValue = void 0;
    let value2 = dataGet(component.serverMemo.data, path);
    if (!firstTime) {
      queueMicrotask(() => {
        callback(value2, oldValue);
        oldValue = value2;
      });
    } else {
      oldValue = value2;
    }
    firstTime = false;
  });
  raxmProperty("$refresh", (component) => (...params) => addMethodAction(component, "$refresh", ...params));
  raxmProperty("$on", (component) => (...params) => listen(component, ...params));
  raxmProperty("$dispatch", (component) => (...params) => dispatch2(component, ...params));
  raxmProperty("$dispatchSelf", (component) => (...params) => dispatchSelf(component, ...params));
  raxmProperty("$dispatchTo", (component) => (...params) => dispatchTo(component, ...params));
  raxmProperty("$upload", (component) => (...params) => upload(component, ...params));
  raxmProperty("$uploadMultiple", (component) => (...params) => uploadMultiple(component, ...params));
  raxmProperty("$removeUpload", (component) => (...params) => removeUpload(component, ...params));
  var parentMemo = /* @__PURE__ */ new WeakMap();
  raxmProperty("$parent", (component) => {
    if (parentMemo.has(component))
      return parentMemo.get(component).$raxm;
    let parent = closestComponent2(component.el.parentElement);
    parentMemo.set(component, parent);
    return parent.$raxm;
  });
  var overriddenMethods = /* @__PURE__ */ new WeakMap();
  function overrideMethod(component, method, callback) {
    if (!overriddenMethods.has(component)) {
      overriddenMethods.set(component, {});
    }
    let obj = overriddenMethods.get(component);
    obj[method] = callback;
    overriddenMethods.set(component, obj);
  }
  raxmFallback((component) => (property2) => async (...params) => {
    if (params.length === 1 && params[0] instanceof Event) {
      params = [];
    }
    if (overriddenMethods.has(component)) {
      let overrides = overriddenMethods.get(component);
      if (typeof overrides[property2] === "function") {
        return overrides[property2](params);
      }
    }
    return await requestCall(component, property2, params);
  });
  function generateRaxmObject(component, state) {
    return new Proxy({}, {
      get(object, property2) {
        if (property2 === "__instance")
          return component;
        if (property2 in aliases) {
          return (...args) => getProperty(component, aliases[property2])(...args);
        } else if (property2 in properties) {
          return (...args) => getProperty(component, property2)(...args);
        } else if (property2 in state) {
          return state[property2];
        } else if (!["then"].includes(property2)) {
          return getFallback(component)(property2);
        }
      },
      set(obj, property2, value2) {
        if (property2 in state) {
          state[property2] = value2;
        }
        return true;
      }
    });
  }

  // js/component.js
  var Component = class {
    constructor(el, connection) {
      if (el.__raxm)
        throw "Component already initialized";
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
        throw new `Initial data missing on Axm component with id: `() + this.id;
      }
      this.fingerprint = initialData.fingerprint;
      this.serverMemo = initialData.serverMemo;
      this.effects = initialData.effects;
      this.listeners = this.effects.listeners;
      this.updateQueue = [];
      this.deferredActions = {};
      this.tearDownCallbacks = [];
      this.messageInTransit = void 0;
      this.scopedListeners = new MessageBus();
      this.prefetchManager = new PrefetchManager_default(this);
      this.watchers = {};
      this.genericLoadingEls = {};
      store_default.callHook("component.initialized", this);
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
      let countElementsBeforeMarker = (el, carryCount = 0) => {
        if (!el)
          return carryCount;
        if (el.nodeType === Node.COMMENT_NODE && el.textContent.includes(`${PREFIX_STRING}-end:${this.id}`))
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
        (el) => node_initializer_default.initialize(el, this),
        (el) => store_default.addComponent(new Component(el, this.connection))
      );
    }
    getPropertyValueIncludingDefers(name) {
      let action = this.deferredActions[name];
      if (!action)
        return this.get(name);
      return action.payload.value;
    }
    updateServerMemoFromResponseAndMergeBackIntoResponse(message) {
      Object.entries(message.response.serverMemo).forEach(([key2, value2]) => {
        if (key2 === "data") {
          Object.entries(value2 || {}).forEach(([dataKey, dataValue]) => {
            this.serverMemo.data[dataKey] = dataValue;
            if (message.shouldSkipWatcherForDataKey(dataKey))
              return;
            Object.entries(this.watchers).forEach(([key3, watchers]) => {
              let originalSplitKey = key3.split(".");
              let basePropertyName = originalSplitKey.shift();
              let restOfPropertyName = originalSplitKey.join(".");
              if (basePropertyName == dataKey) {
                let potentiallyNestedValue = !!restOfPropertyName ? dataGet(dataValue, restOfPropertyName) : dataValue;
                watchers.forEach(
                  (watcher) => watcher(potentiallyNestedValue)
                );
              }
            });
          });
        } else {
          this.serverMemo[key2] = value2;
        }
      });
      message.response.serverMemo = Object.assign({}, this.serverMemo);
    }
    incribeInitialDataOnElement() {
      let el = this.el;
      el.setAttribute(`${PREFIX_DISPLAY}initial-data`, this.encodeIData);
    }
    watch(name, callback) {
      if (!this.watchers[name])
        this.watchers[name] = [];
      this.watchers[name].push(callback);
    }
    on(event, callback) {
      this.scopedListeners.register(event, callback);
    }
    fireMessage() {
      if (this.messageInTransit)
        return;
      Object.entries(this.deferredActions).forEach(([modelName, action]) => {
        this.updateQueue.unshift(action);
      });
      this.deferredActions = {};
      this.messageInTransit = new Message(this, this.updateQueue);
      let sendMessage = () => {
        this.connection.sendMessage(this.messageInTransit);
        store_default.callHook("message.sent", this.messageInTransit, this);
        this.updateQueue = [];
      };
      if (window.capturedRequestsForDusk) {
        window.capturedRequestsForDusk.push(sendMessage);
      } else {
        sendMessage();
      }
    }
    messageSendFailed() {
      if (!this.messageInTransit)
        return;
      store_default.callHook("message.failed", this.messageInTransit, this);
      this.messageInTransit.reject();
      this.messageInTransit = null;
    }
    receiveMessage(message, payload) {
      message.storeResponse(payload);
      if (message instanceof PrefetchMessage)
        return;
      this.handleResponse(message);
      if (this.updateQueue.length > 0) {
        this.fireMessage();
      }
      dispatch(document, "Raxm:update");
    }
    handleResponse(message) {
      let response = message.response;
      this.updateServerMemoFromResponseAndMergeBackIntoResponse(message);
      store_default.callHook("message.received", message, this);
      if (response.effects.html) {
        this.lastFreshHtml = response.effects.html;
        this.handleMorph(response.effects.html.trim());
      } else {
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
              store_default.emitSelf(this.id, event.event, ...event.params);
            } else if (event.to) {
              store_default.emitTo(event.to, event.event, ...event.params);
            } else if (event.ancestorsOnly) {
              store_default.emitUp(this.el, event.event, ...event.params);
            } else {
              store_default.emit(event.event, ...event.params);
            }
          });
        }
        if (response.effects.dispatches && response.effects.dispatches.length > 0) {
          response.effects.dispatches.forEach((event) => {
            const data = event.data ? event.data : {};
            const e = new CustomEvent(event.event, {
              bubbles: true,
              detail: data
            });
            this.el.dispatchEvent(e);
          });
        }
      }
      store_default.callHook("message.processed", message, this);
      if (response.effects.redirect) {
        setTimeout(() => this.redirect(response.effects.redirect));
        return;
      }
    }
    redirect(url2) {
      window.location.href = url2;
    }
    forceRefreshDataBoundElementsMarkedAsDirty(dirtyInputs) {
      this.walk((el) => {
        let directives = getDirectives(el);
        if (directives.missing("model"))
          return;
        const modelValue = directives.get("model").value;
        if (!(el.nodeName == "SELECT" && !el.multiple) && dom_default.hasFocus(el) && !dirtyInputs.includes(modelValue))
          return;
        dom_default.setInputValueFromModel(el, this);
      });
    }
    handleMorph(dom) {
      trigger2("effects", this.el, dom);
    }
    walk(callback, callbackWhenNewComponentIsEncountered = (el) => {
    }) {
      walk(this.el, (el) => {
        if (el.isSameNode(this.el)) {
          callback(el);
          return;
        }
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
  };

  // js/modal.js
  function showHtmlModal(html) {
    let modal = document.getElementById("raxm-error");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "raxm-error";
      modal.style.cssText = `
            position: fixed;
            width: 100vw;
            height: 100vh;
            padding: 50px;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 200000;
        `;
      modal.addEventListener("click", () => hideHtmlModal(modal));
      modal.tabIndex = 0;
      document.body.prepend(modal);
      document.body.style.overflow = "hidden";
    }
    let iframe = document.createElement("iframe");
    iframe.style.cssText = `
        background-color: #17161A;
        border-radius: 5px;
        width: 100%;
        height: 100%;
    `;
    modal.innerHTML = "";
    modal.appendChild(iframe);
    iframe.contentWindow.document.open();
    iframe.contentWindow.document.write(html);
    iframe.contentWindow.document.close();
    modal.addEventListener("keydown", (e) => {
      if (e.key === "Escape")
        hideHtmlModal(modal);
    });
    modal.focus();
  }
  function hideHtmlModal(modal) {
    modal.outerHTML = "";
    document.body.style.overflow = "visible";
  }

  // js/connection/request.js
  var updateUri = document.querySelector("[data-baseUrl]")?.getAttribute("data-baseUrl") ?? window.raxmScriptConfig["baseUrl"] ?? null;
  var Connection = class {
    constructor() {
      this.headers = {};
    }
    onMessage(message, payload) {
      message.component.receiveMessage(message, payload);
    }
    onError(message, status, response) {
      message.component.messageSendFailed();
      return store_default.onErrorCallback(status, response);
    }
    showExpiredMessage(response, message) {
      if (store_default.sessionHasExpiredCallback) {
        store_default.sessionHasExpiredCallback(response, message);
      } else {
        confirm(
          "This page has expired.\nWould you like to refresh the page?"
        ) && window.location.reload();
      }
    }
    async sendMessage(message) {
      const data = message.payload();
      const csrfToken = getCsrfToken();
      const url2 = updateUri;
      const method = "POST";
      try {
        const headers = this.buildHeaders(csrfToken, this.headers);
        const response = await fetch(`${url2}/raxm/update/${data.fingerprint.name}`, {
          method,
          body: JSON.stringify(data),
          credentials: "same-origin",
          headers
        });
        if (response.redirected) {
          window.location.href = response.url;
        }
        if (response.ok) {
          const responseText = await response.text();
          if (contentIsFromDump(responseText)) {
            [dump, content] = splitDumpFromContent(responseText);
            this.onError(message);
            showHtmlModal(dump);
          } else {
            this.onMessage(message, JSON.parse(responseText));
          }
        } else {
          this.handleErrorResponse(response, message, this);
        }
      } catch (error) {
        this.onError(message);
      }
    }
    buildHeaders(csrfToken, customHeaders) {
      const headers = {
        "Content-Type": "application/json",
        "Accept": "text/html, application/xhtml+xml",
        "X-Requested-With": "XMLHttpRequest",
        "X-Axm": true,
        ...customHeaders
      };
      if (csrfToken) {
        headers["X-CSRF-TOKEN"] = csrfToken;
      }
      return headers;
    }
    handleErrorResponse(response, message, context) {
      if (context.onError(message, response.status, response) === false)
        return;
      if (response.status === 419 && !store_default.sessionHasExpired) {
        store_default.sessionHasExpired = true;
        context.showExpiredMessage(response, message);
      } else {
        response.text().then((responseText) => {
          showHtmlModal(responseText);
        });
      }
    }
  };

  // js/boot.js
  function start() {
    dispatch(document, "raxm:init");
    dispatch(document, "raxm:initializing");
    dom_default.rootComponentElementsWithNoParents().forEach((el) => {
      store_default.addComponent(new Component(el, new Connection()));
    });
    dispatch("raxm:load");
    document.addEventListener(
      "visibilitychange",
      () => {
        store_default.raxmIsInBackground = document.hidden;
      },
      false
    );
    store_default.initialRenderIsFinished = true;
    setTimeout(() => window.Raxm.initialRenderIsFinished = true);
    dispatch(document, "raxm:initialized");
  }
  function stop() {
  }
  function rescan() {
  }

  // js/features/supportAlpine.js
  function alpinifyElementsForMorphdom(from, to) {
    if (isV3()) {
      return alpinifyElementsForMorphdomV3(from, to);
    }
    if (from.__x) {
      window.Alpine.clone(from.__x, to);
    }
    if (Array.from(from.attributes).map((attr) => attr.name).some((name) => /x-show/.test(name))) {
      if (from.__x_transition) {
        from.skipElUpdatingButStillUpdateChildren = true;
      } else {
        if (isHiding(from, to)) {
          let style = to.getAttribute("style");
          if (style) {
            to.setAttribute("style", style.replace("display: none;", ""));
          }
        } else if (isShowing(from, to)) {
          to.style.display = from.style.display;
        }
      }
    }
  }
  function alpinifyElementsForMorphdomV3(from, to) {
    if (from.nodeType !== 1)
      return;
    if (from._x_dataStack) {
      window.Alpine.clone(from, to);
    }
  }
  function isHiding(from, to) {
    if (beforeAlpineTwoPointSevenPointThree()) {
      return from.style.display === "" && to.style.display === "none";
    }
    return from.__x_is_shown && !to.__x_is_shown;
  }
  function isShowing(from, to) {
    if (beforeAlpineTwoPointSevenPointThree()) {
      return from.style.display === "none" && to.style.display === "";
    }
    return !from.__x_is_shown && to.__x_is_shown;
  }
  function beforeAlpineTwoPointSevenPointThree() {
    let [major, minor, patch] = window.Alpine.version.split(".").map((i) => Number(i));
    return major <= 2 && minor <= 7 && patch <= 2;
  }
  function isV3() {
    return window.Alpine && window.Alpine.version && /^3\..+\..+$/.test(window.Alpine.version);
  }

  // js/features/supportDisablingFormsDuringRequest.js
  var cleanupStackByComponentId = {};
  store_default.registerHook("element.initialized", (el, component) => {
    let directives = getDirectives(el);
    if (directives.missing("submit"))
      return;
    el.addEventListener("submit", () => {
      cleanupStackByComponentId[component.id] = [];
      component.walk((node) => {
        if (!el.contains(node))
          return;
        if (node.hasAttribute(`${PREFIX_REGEX}ignore`))
          return false;
        if (node.tagName.toLowerCase() === "button" && node.type === "submit" || node.tagName.toLowerCase() === "select" || node.tagName.toLowerCase() === "input" && (node.type === "checkbox" || node.type === "radio")) {
          if (!node.disabled)
            cleanupStackByComponentId[component.id].push(
              () => node.disabled = false
            );
          node.disabled = true;
        } else if (node.tagName.toLowerCase() === "input" || node.tagName.toLowerCase() === "textarea") {
          if (!node.readOnly)
            cleanupStackByComponentId[component.id].push(
              () => node.readOnly = false
            );
          node.readOnly = true;
        }
      });
    });
  });
  store_default.registerHook("message.failed", (message, component) => cleanup(component));
  store_default.registerHook("message.received", (message, component) => cleanup(component));
  function cleanup(component) {
    if (!cleanupStackByComponentId[component.id])
      return;
    while (cleanupStackByComponentId[component.id].length > 0) {
      cleanupStackByComponentId[component.id].shift()();
    }
  }

  // js/features/supportFileDownloads.js
  store_default.registerHook("message.received", (message, component) => {
    let response = message.response;
    if (!response.effects.download)
      return;
    let urlObject = window.webkitURL || window.URL;
    let url2 = urlObject.createObjectURL(
      base64toBlob(response.effects.download.content, response.effects.download.contentType)
    );
    let invisibleLink = document.createElement("a");
    invisibleLink.style.display = "none";
    invisibleLink.href = url2;
    invisibleLink.download = response.effects.download.name;
    document.body.appendChild(invisibleLink);
    invisibleLink.click();
    setTimeout(function() {
      urlObject.revokeObjectURL(url2);
    }, 0);
  });
  function base64toBlob(b64Data, contentType = "", sliceSize = 512) {
    const byteCharacters = atob(b64Data);
    const byteArrays = [];
    if (contentType === null)
      contentType = "";
    for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
      let slice = byteCharacters.slice(offset, offset + sliceSize);
      let byteNumbers = new Array(slice.length);
      for (let i = 0; i < slice.length; i++) {
        byteNumbers[i] = slice.charCodeAt(i);
      }
      let byteArray = new Uint8Array(byteNumbers);
      byteArrays.push(byteArray);
    }
    return new Blob(byteArrays, { type: contentType });
  }

  // js/features/supportStacks.js
  store_default.registerHook("message.received", (message, component) => {
    let response = message.response;
    if (!response.effects.forStack)
      return;
    let updates = [];
    response.effects.forStack.forEach(({ key: key2, stack, type, contents }) => {
      let startEl = document.querySelector(`[axm-stack="${stack}"]`);
      let endEl = document.querySelector(`[axm-end-stack="${stack}"]`);
      if (!startEl || !endEl)
        return;
      if (keyHasAlreadyBeenAddedToTheStack(startEl, endEl, key2))
        return;
      let prepend = (el) => startEl.parentElement.insertBefore(el, startEl.nextElementSibling);
      let push = (el) => endEl.parentElement.insertBefore(el, endEl);
      let frag = createFragment(contents);
      updates.push(() => type === "push" ? push(frag) : prepend(frag));
    });
    while (updates.length > 0)
      updates.shift()();
  });
  function keyHasAlreadyBeenAddedToTheStack(startEl, endEl, key2) {
    let findKeyMarker = (el) => {
      if (el.isSameNode(endEl))
        return;
      return el.matches(`[axm-stack-key="${key2}"]`) ? el : findKeyMarker(el.nextElementSibling);
    };
    return findKeyMarker(startEl);
  }
  function createFragment(html) {
    return document.createRange().createContextualFragment(html);
  }

  // js/features/supportJsEvaluation.js
  store_default.registerHook("message.received", (message, component) => {
    let response = message.response;
    let js = response.effects.js;
    let xjs = response.effects.xjs;
    if (js) {
      Object.entries(js).forEach(([method, body]) => {
        overrideMethod(component, method, () => {
          evaluate(component.el, body);
        });
      });
    }
    if (xjs) {
      xjs.forEach((expression) => {
        evaluate(component.el, expression);
      });
    }
  });
  var cache = /* @__PURE__ */ new Map();
  async function evaluate(code, context) {
    const sandbox = new InMemorySandbox(context);
    let result;
    try {
      result = await new Function("sandbox", `with (sandbox) { ${code} }`).call(null, sandbox);
    } catch (error) {
      handleError(error);
    }
    if (typeof result === "string") {
      result = sanitize(result);
    }
    const output = {
      result,
      context,
      key
    };
    cache.set(key, output);
    return output;
  }
  function handleError(error) {
    console.error(error);
    throw new Error("Evaluation failed");
  }
  function sanitize(value2) {
    const sanitizedValue = value2.replace(/<[^>]*>/g, "");
    return sanitizedValue;
  }
  var InMemorySandbox = class {
    constructor(context) {
      this.context = context;
      this.objects = /* @__PURE__ */ new Map();
    }
    get(name) {
      if (this.context.hasOwnProperty(name)) {
        return this.context[name];
      } else if (this.objects.has(name)) {
        return this.objects.get(name);
      } else {
        return void 0;
      }
    }
    set(name, value2) {
      this.objects.set(name, value2);
    }
    call(name, ...args) {
      const object = this.get(name);
      if (object === void 0) {
        throw new Error(`No existe el objeto ${name}`);
      }
      return object.apply(this, args);
    }
    create(constructor, ...args) {
      const object = new constructor(...args);
      this.objects.set(object.constructor.name, object);
      return object;
    }
  };

  // js/lib/nprogress.js
  var NProgress = {
    version: "0.2.0",
    settings: {
      minimum: 0.08,
      easing: "linear",
      positionUsing: "",
      speed: 200,
      trickle: true,
      trickleSpeed: 200,
      showSpinner: true,
      barSelector: '[role="bar"]',
      spinnerSelector: '[role="spinner"]',
      parent: "body",
      template: `
            <div class="bar" role="bar"><div class="peg"></div></div>
            <div class="spinner" role="spinner"><div class="spinner-icon"></div></div>
        `
    },
    status: null,
    configure(options) {
      Object.assign(this.settings, options);
      return this;
    },
    set(n) {
      const started = this.isStarted();
      n = this.clamp(n, this.settings.minimum, 1);
      this.status = n === 1 ? null : n;
      const progress2 = this.render(!started);
      const bar = progress2.querySelector(this.settings.barSelector);
      const { speed, easing } = this.settings;
      progress2.offsetWidth;
      this.queue((next) => {
        if (this.settings.positionUsing === "") {
          this.settings.positionUsing = this.getPositioningCSS();
        }
        this.css(bar, this.barPositionCSS(n, speed, easing));
        if (n === 1) {
          this.css(progress2, {
            transition: "none",
            opacity: 1
          });
          progress2.offsetWidth;
          setTimeout(() => {
            this.css(progress2, {
              transition: `all ${speed}ms linear`,
              opacity: 0
            });
            setTimeout(() => {
              this.remove();
              next();
            }, speed);
          }, speed);
        } else {
          setTimeout(next, speed);
        }
      });
      return this;
    },
    isStarted() {
      return typeof this.status === "number";
    },
    start() {
      if (!this.status) {
        this.set(0);
      }
      const work = () => {
        setTimeout(() => {
          if (!this.status)
            return;
          this.trickle();
          work();
        }, this.settings.trickleSpeed);
      };
      if (this.settings.trickle)
        work();
      return this;
    },
    done(force) {
      if (!force && !this.status)
        return this;
      return this.inc(0.3 + 0.5 * Math.random()).set(1);
    },
    inc(amount) {
      let n = this.status;
      if (!n) {
        return this.start();
      } else if (n > 1) {
        return;
      } else {
        if (typeof amount !== "number") {
          if (n >= 0 && n < 0.2) {
            amount = 0.1;
          } else if (n >= 0.2 && n < 0.5) {
            amount = 0.04;
          } else if (n >= 0.5 && n < 0.8) {
            amount = 0.02;
          } else if (n >= 0.8 && n < 0.99) {
            amount = 5e-3;
          } else {
            amount = 0;
          }
        }
        n = this.clamp(n + amount, 0, 0.994);
        return this.set(n);
      }
    },
    trickle() {
      return this.inc();
    },
    render(fromStart) {
      if (this.isRendered())
        return document.getElementById("nprogress");
      this.addClass(document.documentElement, "nprogress-busy");
      const progress2 = document.createElement("div");
      progress2.id = "nprogress";
      progress2.innerHTML = this.settings.template;
      const bar = progress2.querySelector(this.settings.barSelector);
      const perc = fromStart ? "-100" : this.toBarPerc(this.status || 0);
      const parent = this.isDOM(this.settings.parent) ? this.settings.parent : document.querySelector(this.settings.parent);
      let spinner;
      this.css(bar, {
        transition: "all 0 linear",
        transform: `translate3d(${perc}%,0,0)`
      });
      if (!this.settings.showSpinner) {
        spinner = progress2.querySelector(this.settings.spinnerSelector);
        spinner && this.removeElement(spinner);
      }
      if (parent != document.body) {
        this.addClass(parent, "nprogress-custom-parent");
      }
      parent.appendChild(progress2);
      return progress2;
    },
    remove() {
      this.removeClass(document.documentElement, "nprogress-busy");
      const parent = this.isDOM(this.settings.parent) ? this.settings.parent : document.querySelector(this.settings.parent);
      this.removeClass(parent, "nprogress-custom-parent");
      const progress2 = document.getElementById("nprogress");
      progress2 && this.removeElement(progress2);
    },
    isRendered() {
      return !!document.getElementById("nprogress");
    },
    getPositioningCSS() {
      const bodyStyle = document.body.style;
      const vendorPrefix = "WebkitTransform" in bodyStyle ? "Webkit" : "MozTransform" in bodyStyle ? "Moz" : "msTransform" in bodyStyle ? "ms" : "OTransform" in bodyStyle ? "O" : "";
      return vendorPrefix + "Perspective" in bodyStyle ? "translate3d" : vendorPrefix + "Transform" in bodyStyle ? "translate" : "margin";
    },
    isDOM(obj) {
      if (typeof HTMLElement === "object") {
        return obj instanceof HTMLElement;
      }
      return obj && typeof obj === "object" && obj.nodeType === 1 && typeof obj.nodeName === "string";
    },
    clamp(n, min, max) {
      if (n < min)
        return min;
      if (n > max)
        return max;
      return n;
    },
    toBarPerc(n) {
      return (-1 + n) * 100;
    },
    barPositionCSS(n, speed, ease) {
      let barCSS;
      if (this.settings.positionUsing === "translate3d") {
        barCSS = { transform: `translate3d(${this.toBarPerc(n)}%,0,0)` };
      } else if (this.settings.positionUsing === "translate") {
        barCSS = { transform: `translate(${this.toBarPerc(n)}%,0)` };
      } else {
        barCSS = { "margin-left": `${this.toBarPerc(n)}%` };
      }
      barCSS.transition = `all ${speed}ms ${ease}`;
      return barCSS;
    },
    queue: function() {
      const pending = [];
      function next() {
        const fn = pending.shift();
        if (fn) {
          fn(next);
        }
      }
      return function(fn) {
        pending.push(fn);
        if (pending.length === 1)
          next();
      };
    }(),
    css: (() => {
      const cssPrefixes = ["Webkit", "O", "Moz", "ms"];
      const cssProps = {};
      function camelCase(string) {
        return string.replace(/^-ms-/, "ms-").replace(/-([\da-z])/gi, (match, letter) => letter.toUpperCase());
      }
      function getVendorProp(name) {
        const style = document.body.style;
        if (name in style)
          return name;
        let i = cssPrefixes.length;
        const capName = name.charAt(0).toUpperCase() + name.slice(1);
        let vendorName;
        while (i--) {
          vendorName = cssPrefixes[i] + capName;
          if (vendorName in style)
            return vendorName;
        }
        return name;
      }
      function getStyleProp(name) {
        name = camelCase(name);
        return cssProps[name] || (cssProps[name] = getVendorProp(name));
      }
      return (element, properties2) => {
        let prop, value2;
        if (properties2) {
          for (prop in properties2) {
            value2 = properties2[prop];
            if (value2 !== void 0 && properties2.hasOwnProperty(prop)) {
              const propToApply = getStyleProp(prop);
              element.style[propToApply] = value2;
            }
          }
        }
      };
    })(),
    hasClass(element, name) {
      const list = typeof element === "string" ? element : this.classList(element);
      return list.indexOf(` ${name} `) >= 0;
    },
    addClass(element, name) {
      const oldList = this.classList(element);
      const newList = oldList + name;
      if (!this.hasClass(oldList, name)) {
        element.className = newList.substring(1);
      }
    },
    removeClass(element, name) {
      const oldList = this.classList(element);
      let newList;
      if (this.hasClass(element, name)) {
        newList = oldList.replace(` ${name} `, " ");
        element.className = newList.substring(1, newList.length - 1);
      }
    },
    classList(element) {
      return ` ${element && element.className || ""} `.replace(/\s+/gi, " ");
    },
    removeElement(element) {
      element && element.parentNode && element.parentNode.removeChild(element);
    }
  };
  var nprogress_default = NProgress;

  // js/progress.js
  var timeout = null;
  function addEventListeners(delay) {
    document.addEventListener("raxm:navigating", start2.bind(null, delay));
    document.addEventListener("raxm:navigate", progress);
    document.addEventListener("raxm:navigated", finish);
  }
  function start2(delay) {
    timeout = setTimeout(() => nprogress_default.start(), delay);
  }
  function progress(event) {
    if (nprogress_default.isStarted() && event.detail.progress.percentage) {
      nprogress_default.set(Math.max(nprogress_default.status, event.detail.progress.percentage / 100 * 0.9));
    }
  }
  function finish(event) {
    clearTimeout(timeout);
    if (!nprogress_default.isStarted()) {
      return;
    } else if (event.detail.visit.completed) {
      nprogress_default.done();
    } else if (event.detail.visit.interrupted) {
      nprogress_default.set(0);
    } else if (event.detail.visit.cancelled) {
      nprogress_default.done();
      nprogress_default.remove();
    }
  }
  function injectCSS(color) {
    const element = document.createElement("style");
    element.type = "text/css";
    element.textContent = `
	#nprogress {
		pointer-events: none;
	}

	#nprogress .bar {
		background: ${color};

		position: fixed;
		z-index: 1031;
		top: 0;
		left: 0;

		width: 100%;
		height: 2px;
	}

	#nprogress .peg {
		display: block;
		position: absolute;
		right: 0px;
		width: 100px;
		height: 100%;
		box-shadow: 0 0 10px ${color}, 0 0 5px ${color};
		opacity: 1.0;

		-webkit-transform: rotate(3deg) translate(0px, -4px);
			-ms-transform: rotate(3deg) translate(0px, -4px);
				transform: rotate(3deg) translate(0px, -4px);
	}

	#nprogress .spinner {
		display: block;
		position: fixed;
		z-index: 1031;
		top: 15px;
		right: 15px;
	}

	#nprogress .spinner-icon {
		width: 18px;
		height: 18px;
		box-sizing: border-box;

		border: solid 2px transparent;
		border-top-color: ${color};
		border-left-color: ${color};
		border-radius: 50%;

		-webkit-animation: nprogress-spinner 400ms linear infinite;
				animation: nprogress-spinner 400ms linear infinite;
	}

	.nprogress-custom-parent {
		overflow: hidden;
		position: relative;
	}

	.nprogress-custom-parent #nprogress .spinner,
	.nprogress-custom-parent #nprogress .bar {
		position: absolute;
	}

	@-webkit-keyframes nprogress-spinner {
		0%   { -webkit-transform: rotate(0deg); }
		100% { -webkit-transform: rotate(360deg); }
	}
	@keyframes nprogress-spinner {
		0%   { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	}
	`;
    document.head.appendChild(element);
  }
  var Progress = {
    init({ delay = 250, color = "#29d", includeCSS = true, showSpinner = false } = {}) {
      addEventListeners(delay);
      nprogress_default.configure({ showSpinner });
      if (includeCSS) {
        injectCSS(color);
      }
    }
  };
  var progress_default = Progress;

  // js/directives/axm-navigate.js
  directive("navigate", ({ el, directive: directive2, component }) => {
    el.addEventListener("click", navigationManager.handleNavigate);
  });
  var MAX_HISTORY_LENGTH = 10;
  var attributesExemptFromScriptTagHashing = ["data-csrf"];
  var NavigationManager = class {
    constructor() {
      this.oldBodyScriptTagHashes = [];
      this.handleNavigate = this.handleNavigate.bind(this);
      window.addEventListener("popstate", this.handlePopState.bind(this));
    }
    handleNavigate(event) {
      if (this.shouldInterceptClick(event))
        return;
      event.preventDefault();
      const newUrl = event.target.getAttribute("href");
      this.navigateTo(newUrl);
    }
    shouldInterceptClick(event) {
      return event.which > 1 || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey;
    }
    async navigateTo(url2) {
      this.updateHistoryStateForCurrentPage();
      const response = await loadView(url2);
      const pageState = { html: response.html };
      const urlObject = new URL(url2, document.baseURI);
      if (window.location.href === urlObject.href) {
        this.replaceState(urlObject, pageState.html);
      } else {
        this.pushState(urlObject, pageState.html);
      }
      renderView(response.html);
    }
    handlePopState(e) {
      const state = e.state;
      if (state && state.raxm && state.raxm._html) {
        renderView(this.fromSessionStorage(state.raxm._html));
      } else {
        this.navigateTo(window.location.href, true);
        return;
      }
      dispatchEvent(new Event("raxm:popstate"));
      window.Raxm.start();
    }
    updateHistoryStateForCurrentPage() {
      const currentPageUrl = new URL(window.location.href, document.baseURI);
      const currentState = {
        html: document.documentElement.outerHTML
      };
      this.replaceState(currentPageUrl, currentState.html);
    }
    pushState(url2, html) {
      this.updateState("pushState", url2, html);
    }
    replaceState(url2, html) {
      this.updateState("replaceState", url2, html);
    }
    updateState(method, url2, html) {
      this.clearState();
      let key2 = new Date().getTime();
      this.tryToStoreInSession(key2, html);
      let state = history.state || {};
      if (!state.raxm)
        state.raxm = {};
      state.raxm._html = key2;
      try {
        history[method](state, document.title, url2);
      } catch (error) {
        if (error instanceof DOMException && error.name === "SecurityError") {
          console.error("Raxm: You can't use axm:navigate with a link to a different root domain: " + url2);
        }
      }
    }
    clearState() {
      const currentHistory = window.history.state || {};
      const historyData = currentHistory.raxm || [];
      if (historyData.length >= MAX_HISTORY_LENGTH) {
        window.history.go(-1);
        historyData.shift();
        currentHistory.raxm = historyData;
        window.history.replaceState(currentHistory, document.title, window.location.href);
      }
    }
    fromSessionStorage(timestamp) {
      let state = JSON.parse(sessionStorage.getItem("raxm:" + timestamp));
      return state;
    }
    tryToStoreInSession(timestamp, value2) {
      try {
        sessionStorage.setItem("raxm:" + timestamp, JSON.stringify(value2));
      } catch (error) {
        if (![22, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14].includes(error.code))
          return;
        let oldestTimestamp = Object.keys(sessionStorage).map((key2) => Number(key2.replace("raxm:", ""))).sort().shift();
        if (!oldestTimestamp)
          return;
        sessionStorage.removeItem("raxm:" + oldestTimestamp);
        this.tryToStoreInSession(timestamp, value2);
      }
    }
  };
  var navigationManager = new NavigationManager();
  async function loadView(url2) {
    document.dispatchEvent(new Event("raxm:navigating"));
    try {
      const response = await fetch(url2);
      const html = await response.text();
      return { html };
    } catch (error) {
      console.error("Error loading view:", error);
      return { html: "" };
    }
  }
  async function renderView(html) {
    const newDocument = new DOMParser().parseFromString(html, "text/html");
    const newBody = document.adoptNode(newDocument.body);
    const newHead = document.adoptNode(newDocument.head);
    const newBodyScriptTagHashes = Array.from(newBody.querySelectorAll("script")).map((i) => {
      return simpleHash(dom_default.ignoreAttributes(i.outerHTML, attributesExemptFromScriptTagHashing));
    });
    mergeNewHead(newHead);
    prepNewBodyScriptTagsToRun(newBody, newBodyScriptTagHashes);
    const oldBody = document.body;
    document.body.replaceWith(newBody);
    document.dispatchEvent(new CustomEvent("raxm:navigated", { detail: { visit: { completed: true } } }));
  }
  function simpleHash(str) {
    return str.split("").reduce((a, b) => {
      a = (a << 5) - a + b.charCodeAt(0);
      return a & a;
    }, 0);
  }
  function mergeNewHead(newHead) {
    const children = Array.from(document.head.children);
    const headChildrenHtmlLookup = children.map((i) => i.outerHTML);
    const garbageCollector = document.createDocumentFragment();
    const touchedHeadElements = [];
    for (const child of Array.from(newHead.children)) {
      if (dom_default.isAsset(child)) {
        if (!headChildrenHtmlLookup.includes(child.outerHTML)) {
          if (isTracked(child)) {
            if (ifTheQueryStringChangedSinceLastRequest(child, children)) {
              setTimeout(() => window.location.reload());
            }
          }
          if (dom_default.isScript(child)) {
            document.head.appendChild(dom_default.cloneScriptTag(child));
          } else {
            document.head.appendChild(child);
          }
        } else {
          garbageCollector.appendChild(child);
        }
        touchedHeadElements.push(child);
      }
    }
    for (const child of Array.from(document.head.children)) {
      if (!dom_default.isAsset(child))
        child.remove();
    }
    for (const child of Array.from(newHead.children)) {
      document.head.appendChild(child);
    }
  }
  function ifTheQueryStringChangedSinceLastRequest(el, currentHeadChildren) {
    let [uri, queryString] = extractUriAndQueryString(el);
    return currentHeadChildren.some((child) => {
      if (!isTracked(child))
        return false;
      let [currentUri, currentQueryString] = extractUriAndQueryString(child);
      if (currentUri === uri && queryString !== currentQueryString)
        return true;
    });
  }
  function extractUriAndQueryString(el) {
    let url2 = dom_default.isScript(el) ? el.src : el.href;
    return url2.split("?");
  }
  function prepNewBodyScriptTagsToRun(newBody, newBodyScriptTagHashes) {
    newBody.querySelectorAll("script").forEach((i) => {
      if (i.hasAttribute("data-navigate-once")) {
        let hash = simpleHash(
          dom_default.ignoreAttributes(i.outerHTML, attributesExemptFromScriptTagHashing)
        );
        if (newBodyScriptTagHashes.includes(hash))
          return;
      }
      i.replaceWith(dom_default.cloneScriptTag(i));
    });
  }
  function isTracked(el) {
    return el.hasAttribute("data-navigate-track");
  }
  function navigateTo(url2) {
    navigationManager.navigateTo(url2, true);
  }

  // js/features/supportNavigate.js
  var isNavigating = false;
  var progressBar = true;
  shouldHideProgressBar() && disableProgressBar();
  function shouldRedirectUsingNavigateOr(effects, url2, or) {
    let forceNavigate = effects.redirectUsingNavigate;
    if (forceNavigate || isNavigating) {
      navigateTo(url2);
    } else {
      or();
    }
  }
  function shouldHideProgressBar() {
    if (!!document.querySelector("[data-no-progress-bar]"))
      return true;
    if (progressBar)
      return true;
    return false;
  }
  function disableProgressBar() {
    if (progressBar) {
      progress_default.init();
    }
  }

  // js/features/supportRedirects.js
  store_default.registerHook("message.received", (message, component) => {
    let effects = message.response.effects;
    if (!effects["redirect"])
      return;
    shouldRedirectUsingNavigateOr(effects, url, () => {
      window.location.href = url;
    });
  });

  // js/dom/morphdom/morphAttrs.js
  function morphAttrs(fromNode, toNode) {
    if (fromNode._x_isShown !== void 0 && toNode._x_isShown !== void 0)
      return;
    if (fromNode._x_isShown && !toNode._x_isShown)
      return;
    if (!fromNode._x_isShown && toNode._x_isShown)
      return;
    var attrs = toNode.attributes;
    var i;
    var attr;
    var attrName;
    var attrNamespaceURI;
    var attrValue;
    var fromValue;
    for (i = attrs.length - 1; i >= 0; --i) {
      attr = attrs[i];
      attrName = attr.name;
      attrNamespaceURI = attr.namespaceURI;
      attrValue = attr.value;
      if (attrNamespaceURI) {
        attrName = attr.localName || attrName;
        fromValue = fromNode.getAttributeNS(attrNamespaceURI, attrName);
        if (fromValue !== attrValue) {
          if (attr.prefix === "xmlns") {
            attrName = attr.name;
          }
          fromNode.setAttributeNS(attrNamespaceURI, attrName, attrValue);
        }
      } else {
        fromValue = fromNode.getAttribute(attrName);
        if (fromValue !== attrValue) {
          fromNode.setAttribute(attrName, attrValue);
        }
      }
    }
    attrs = fromNode.attributes;
    for (i = attrs.length - 1; i >= 0; --i) {
      attr = attrs[i];
      if (attr.specified !== false) {
        attrName = attr.name;
        attrNamespaceURI = attr.namespaceURI;
        if (attrNamespaceURI) {
          attrName = attr.localName || attrName;
          if (!toNode.hasAttributeNS(attrNamespaceURI, attrName)) {
            fromNode.removeAttributeNS(attrNamespaceURI, attrName);
          }
        } else {
          if (!toNode.hasAttribute(attrName)) {
            fromNode.removeAttribute(attrName);
          }
        }
      }
    }
  }

  // js/dom/morphdom/specialElHandlers.js
  function syncBooleanAttrProp(fromEl, toEl, name) {
    if (fromEl[name] !== toEl[name]) {
      fromEl[name] = toEl[name];
      if (fromEl[name]) {
        fromEl.setAttribute(name, "");
      } else {
        fromEl.removeAttribute(name);
      }
    }
  }
  var specialElHandlers_default = {
    OPTION: function(fromEl, toEl) {
      var parentNode = fromEl.parentNode;
      if (parentNode) {
        var parentName = parentNode.nodeName.toUpperCase();
        if (parentName === "OPTGROUP") {
          parentNode = parentNode.parentNode;
          parentName = parentNode && parentNode.nodeName.toUpperCase();
        }
        if (parentName === "SELECT" && !parentNode.hasAttribute("multiple")) {
          if (fromEl.hasAttribute("selected") && !toEl.selected) {
            fromEl.setAttribute("selected", "selected");
            fromEl.removeAttribute("selected");
          }
          parentNode.selectedIndex = -1;
        }
      }
      syncBooleanAttrProp(fromEl, toEl, "selected");
    },
    INPUT: function(fromEl, toEl) {
      syncBooleanAttrProp(fromEl, toEl, "checked");
      syncBooleanAttrProp(fromEl, toEl, "disabled");
      if (fromEl.value !== toEl.value) {
        fromEl.value = toEl.value;
      }
      if (!toEl.hasAttribute("value")) {
        fromEl.removeAttribute("value");
      }
    },
    TEXTAREA: function(fromEl, toEl) {
      var newValue = toEl.value;
      if (fromEl.value !== newValue) {
        fromEl.value = newValue;
      }
      var firstChild = fromEl.firstChild;
      if (firstChild) {
        var oldValue = firstChild.nodeValue;
        if (oldValue == newValue || !newValue && oldValue == fromEl.placeholder) {
          return;
        }
        firstChild.nodeValue = newValue;
      }
    },
    SELECT: function(fromEl, toEl) {
      if (!toEl.hasAttribute("multiple")) {
        var selectedIndex = -1;
        var i = 0;
        var curChild = fromEl.firstChild;
        var optgroup;
        var nodeName;
        while (curChild) {
          nodeName = curChild.nodeName && curChild.nodeName.toUpperCase();
          if (nodeName === "OPTGROUP") {
            optgroup = curChild;
            curChild = optgroup.firstChild;
          } else {
            if (nodeName === "OPTION") {
              if (curChild.hasAttribute("selected")) {
                selectedIndex = i;
                break;
              }
              i++;
            }
            curChild = curChild.nextSibling;
            if (!curChild && optgroup) {
              curChild = optgroup.nextSibling;
              optgroup = null;
            }
          }
        }
        fromEl.selectedIndex = selectedIndex;
      }
    }
  };

  // js/dom/morphdom/util.js
  var range;
  var NS_XHTML = "http://www.w3.org/1999/xhtml";
  var doc = typeof document === "undefined" ? void 0 : document;
  var HAS_TEMPLATE_SUPPORT = !!doc && "content" in doc.createElement("template");
  var HAS_RANGE_SUPPORT = !!doc && doc.createRange && "createContextualFragment" in doc.createRange();
  function createFragmentFromTemplate(str) {
    var template = doc.createElement("template");
    template.innerHTML = str;
    return template.content.childNodes[0];
  }
  function createFragmentFromRange(str) {
    if (!range) {
      range = doc.createRange();
      range.selectNode(doc.body);
    }
    var fragment = range.createContextualFragment(str);
    return fragment.childNodes[0];
  }
  function createFragmentFromWrap(str) {
    var fragment = doc.createElement("body");
    fragment.innerHTML = str;
    return fragment.childNodes[0];
  }
  function toElement(str) {
    str = str.trim();
    if (HAS_TEMPLATE_SUPPORT) {
      return createFragmentFromTemplate(str);
    } else if (HAS_RANGE_SUPPORT) {
      return createFragmentFromRange(str);
    }
    return createFragmentFromWrap(str);
  }
  function compareNodeNames(fromEl, toEl) {
    var fromNodeName = fromEl.nodeName;
    var toNodeName = toEl.nodeName;
    if (fromNodeName === toNodeName) {
      return true;
    }
    if (toEl.actualize && fromNodeName.charCodeAt(0) < 91 && toNodeName.charCodeAt(0) > 90) {
      return fromNodeName === toNodeName.toUpperCase();
    } else {
      return false;
    }
  }
  function createElementNS(name, namespaceURI) {
    return !namespaceURI || namespaceURI === NS_XHTML ? doc.createElement(name) : doc.createElementNS(namespaceURI, name);
  }
  function moveChildren(fromEl, toEl) {
    var curChild = fromEl.firstChild;
    while (curChild) {
      var nextChild = curChild.nextSibling;
      toEl.appendChild(curChild);
      curChild = nextChild;
    }
    return toEl;
  }

  // js/dom/morphdom/morphdom.js
  var ELEMENT_NODE = 1;
  var DOCUMENT_FRAGMENT_NODE = 11;
  var TEXT_NODE = 3;
  var COMMENT_NODE = 8;
  function noop() {
  }
  function defaultGetNodeKey(node) {
    return node.id;
  }
  function callHook(hook2, ...params) {
    if (hook2.name !== "getNodeKey" && hook2.name !== "onBeforeElUpdated") {
    }
    if (typeof params[0].hasAttribute !== "function")
      return;
    return hook2(...params);
  }
  function morphdomFactory(morphAttrs2) {
    return function morphdom2(fromNode, toNode, options) {
      if (!options) {
        options = {};
      }
      if (typeof toNode === "string") {
        if (fromNode.nodeName === "#document" || fromNode.nodeName === "HTML") {
          var toNodeHtml = toNode;
          toNode = doc.createElement("html");
          toNode.innerHTML = toNodeHtml;
        } else {
          toNode = toElement(toNode);
        }
      }
      var getNodeKey = options.getNodeKey || defaultGetNodeKey;
      var onBeforeNodeAdded = options.onBeforeNodeAdded || noop;
      var onNodeAdded = options.onNodeAdded || noop;
      var onBeforeElUpdated = options.onBeforeElUpdated || noop;
      var onElUpdated = options.onElUpdated || noop;
      var onBeforeNodeDiscarded = options.onBeforeNodeDiscarded || noop;
      var onNodeDiscarded = options.onNodeDiscarded || noop;
      var onBeforeElChildrenUpdated = options.onBeforeElChildrenUpdated || noop;
      var childrenOnly = options.childrenOnly === true;
      var fromNodesLookup = /* @__PURE__ */ Object.create(null);
      var keyedRemovalList = [];
      function addKeyedRemoval(key2) {
        keyedRemovalList.push(key2);
      }
      function walkDiscardedChildNodes(node, skipKeyedNodes) {
        if (node.nodeType === ELEMENT_NODE) {
          var curChild = node.firstChild;
          while (curChild) {
            var key2 = void 0;
            if (skipKeyedNodes && (key2 = callHook(getNodeKey, curChild))) {
              addKeyedRemoval(key2);
            } else {
              callHook(onNodeDiscarded, curChild);
              if (curChild.firstChild) {
                walkDiscardedChildNodes(curChild, skipKeyedNodes);
              }
            }
            curChild = curChild.nextSibling;
          }
        }
      }
      function removeNode(node, parentNode, skipKeyedNodes) {
        if (callHook(onBeforeNodeDiscarded, node) === false) {
          return;
        }
        if (parentNode) {
          parentNode.removeChild(node);
        }
        callHook(onNodeDiscarded, node);
        walkDiscardedChildNodes(node, skipKeyedNodes);
      }
      function indexTree(node) {
        if (node.nodeType === ELEMENT_NODE || node.nodeType === DOCUMENT_FRAGMENT_NODE) {
          var curChild = node.firstChild;
          while (curChild) {
            var key2 = callHook(getNodeKey, curChild);
            if (key2) {
              fromNodesLookup[key2] = curChild;
            }
            indexTree(curChild);
            curChild = curChild.nextSibling;
          }
        }
      }
      indexTree(fromNode);
      function handleNodeAdded(el) {
        callHook(onNodeAdded, el);
        if (el.skipAddingChildren) {
          return;
        }
        var curChild = el.firstChild;
        while (curChild) {
          var nextSibling = curChild.nextSibling;
          var key2 = callHook(getNodeKey, curChild);
          if (key2) {
            var unmatchedFromEl = fromNodesLookup[key2];
            if (unmatchedFromEl && compareNodeNames(curChild, unmatchedFromEl)) {
              curChild.parentNode.replaceChild(unmatchedFromEl, curChild);
              morphEl(unmatchedFromEl, curChild);
            } else {
              handleNodeAdded(curChild);
            }
          } else {
            handleNodeAdded(curChild);
          }
          curChild = nextSibling;
        }
      }
      function cleanupFromEl(fromEl, curFromNodeChild, curFromNodeKey) {
        while (curFromNodeChild) {
          var fromNextSibling = curFromNodeChild.nextSibling;
          if (curFromNodeKey = callHook(getNodeKey, curFromNodeChild)) {
            addKeyedRemoval(curFromNodeKey);
          } else {
            removeNode(curFromNodeChild, fromEl, true);
          }
          curFromNodeChild = fromNextSibling;
        }
      }
      function morphEl(fromEl, toEl, childrenOnly2) {
        var toElKey = callHook(getNodeKey, toEl);
        if (toElKey) {
          delete fromNodesLookup[toElKey];
        }
        if (!childrenOnly2) {
          if (callHook(onBeforeElUpdated, fromEl, toEl) === false) {
            return;
          }
          if (!fromEl.skipElUpdatingButStillUpdateChildren) {
            morphAttrs2(fromEl, toEl);
          }
          callHook(onElUpdated, fromEl);
          if (callHook(onBeforeElChildrenUpdated, fromEl, toEl) === false) {
            return;
          }
        }
        if (fromEl.nodeName !== "TEXTAREA") {
          morphChildren(fromEl, toEl);
        } else {
          if (fromEl.innerHTML != toEl.innerHTML) {
            specialElHandlers_default.TEXTAREA(fromEl, toEl);
          }
        }
      }
      function morphChildren(fromEl, toEl) {
        var curToNodeChild = toEl.firstChild;
        var curFromNodeChild = fromEl.firstChild;
        var curToNodeKey;
        var curFromNodeKey;
        var fromNextSibling;
        var toNextSibling;
        var matchingFromEl;
        outer:
          while (curToNodeChild) {
            toNextSibling = curToNodeChild.nextSibling;
            curToNodeKey = callHook(getNodeKey, curToNodeChild);
            while (curFromNodeChild) {
              fromNextSibling = curFromNodeChild.nextSibling;
              if (curToNodeChild.isSameNode && curToNodeChild.isSameNode(curFromNodeChild)) {
                curToNodeChild = toNextSibling;
                curFromNodeChild = fromNextSibling;
                continue outer;
              }
              curFromNodeKey = callHook(getNodeKey, curFromNodeChild);
              var curFromNodeType = curFromNodeChild.nodeType;
              var isCompatible = void 0;
              if (curFromNodeType === curToNodeChild.nodeType) {
                if (curFromNodeType === ELEMENT_NODE) {
                  if (curToNodeKey) {
                    if (curToNodeKey !== curFromNodeKey) {
                      if (matchingFromEl = fromNodesLookup[curToNodeKey]) {
                        if (fromNextSibling === matchingFromEl) {
                          isCompatible = false;
                        } else {
                          fromEl.insertBefore(matchingFromEl, curFromNodeChild);
                          if (curFromNodeKey) {
                            addKeyedRemoval(curFromNodeKey);
                          } else {
                            removeNode(curFromNodeChild, fromEl, true);
                          }
                          curFromNodeChild = matchingFromEl;
                        }
                      } else {
                        isCompatible = false;
                      }
                    }
                  } else if (curFromNodeKey) {
                    isCompatible = false;
                  }
                  isCompatible = isCompatible !== false && compareNodeNames(curFromNodeChild, curToNodeChild);
                  if (isCompatible) {
                    if (!curToNodeChild.isEqualNode(curFromNodeChild) && curToNodeChild.nextElementSibling && curToNodeChild.nextElementSibling.isEqualNode(curFromNodeChild)) {
                      isCompatible = false;
                    } else {
                      morphEl(curFromNodeChild, curToNodeChild);
                    }
                  }
                } else if (curFromNodeType === TEXT_NODE || curFromNodeType == COMMENT_NODE) {
                  isCompatible = true;
                  if (curFromNodeChild.nodeValue !== curToNodeChild.nodeValue) {
                    curFromNodeChild.nodeValue = curToNodeChild.nodeValue;
                  }
                }
              }
              if (isCompatible) {
                curToNodeChild = toNextSibling;
                curFromNodeChild = fromNextSibling;
                continue outer;
              }
              if (curToNodeChild.nextElementSibling && curToNodeChild.nextElementSibling.isEqualNode(curFromNodeChild)) {
                const nodeToBeAdded = curToNodeChild.cloneNode(true);
                fromEl.insertBefore(nodeToBeAdded, curFromNodeChild);
                handleNodeAdded(nodeToBeAdded);
                curToNodeChild = curToNodeChild.nextElementSibling.nextSibling;
                curFromNodeChild = fromNextSibling;
                continue outer;
              } else {
                if (curFromNodeKey) {
                  addKeyedRemoval(curFromNodeKey);
                } else {
                  removeNode(curFromNodeChild, fromEl, true);
                }
              }
              curFromNodeChild = fromNextSibling;
            }
            if (curToNodeKey && (matchingFromEl = fromNodesLookup[curToNodeKey]) && compareNodeNames(matchingFromEl, curToNodeChild)) {
              fromEl.appendChild(matchingFromEl);
              morphEl(matchingFromEl, curToNodeChild);
            } else {
              var onBeforeNodeAddedResult = callHook(onBeforeNodeAdded, curToNodeChild);
              if (onBeforeNodeAddedResult !== false) {
                if (onBeforeNodeAddedResult) {
                  curToNodeChild = onBeforeNodeAddedResult;
                }
                if (curToNodeChild.actualize) {
                  curToNodeChild = curToNodeChild.actualize(fromEl.ownerDocument || doc);
                }
                fromEl.appendChild(curToNodeChild);
                handleNodeAdded(curToNodeChild);
              }
            }
            curToNodeChild = toNextSibling;
            curFromNodeChild = fromNextSibling;
          }
        cleanupFromEl(fromEl, curFromNodeChild, curFromNodeKey);
        var specialElHandler = specialElHandlers_default[fromEl.nodeName];
        if (specialElHandler && !fromEl.isLiveaxmModel) {
          specialElHandler(fromEl, toEl);
        }
      }
      var morphedNode = fromNode;
      var morphedNodeType = morphedNode.nodeType;
      var toNodeType = toNode.nodeType;
      if (!childrenOnly) {
        if (morphedNodeType === ELEMENT_NODE) {
          if (toNodeType === ELEMENT_NODE) {
            if (!compareNodeNames(fromNode, toNode)) {
              callHook(onNodeDiscarded, fromNode);
              morphedNode = moveChildren(fromNode, createElementNS(toNode.nodeName, toNode.namespaceURI));
            }
          } else {
            morphedNode = toNode;
          }
        } else if (morphedNodeType === TEXT_NODE || morphedNodeType === COMMENT_NODE) {
          if (toNodeType === morphedNodeType) {
            if (morphedNode.nodeValue !== toNode.nodeValue) {
              morphedNode.nodeValue = toNode.nodeValue;
            }
            return morphedNode;
          } else {
            morphedNode = toNode;
          }
        }
      }
      if (morphedNode === toNode) {
        callHook(onNodeDiscarded, fromNode);
      } else {
        if (toNode.isSameNode && toNode.isSameNode(morphedNode)) {
          return;
        }
        morphEl(morphedNode, toNode, childrenOnly);
        if (keyedRemovalList) {
          for (var i = 0, len = keyedRemovalList.length; i < len; i++) {
            var elToRemove = fromNodesLookup[keyedRemovalList[i]];
            if (elToRemove) {
              removeNode(elToRemove, elToRemove.parentNode, false);
            }
          }
        }
      }
      if (!childrenOnly && morphedNode !== fromNode && fromNode.parentNode) {
        if (morphedNode.actualize) {
          morphedNode = morphedNode.actualize(fromNode.ownerDocument || doc);
        }
        fromNode.parentNode.replaceChild(morphedNode, fromNode);
      }
      return morphedNode;
    };
  }

  // js/dom/morphdom/index.js
  var morphdom = morphdomFactory(morphAttrs);
  var morphdom_default = morphdom;

  // js/morph.js
  function morph(el, html) {
    let morphChanges = { changed: [], added: [], removed: [] };
    let id = el.__raxm.id;
    morphdom_default(el, html, {
      childrenOnly: false,
      getNodeKey: (node) => {
        if (isntElement(node))
          return;
        return node.hasAttribute(`${PREFIX_REGEX}key`) ? node.getAttribute(`${PREFIX_REGEX}key`) : node.hasAttribute(`${PREFIX_DISPLAY}id`) ? node.getAttribute(`${PREFIX_REGEX}id`) : node.id;
      },
      onBeforeNodeAdded: (node) => {
      },
      onBeforeNodeDiscarded: (node) => {
        if (node.__x_inserted_me && Array.from(node.attributes).some(
          (attr) => /x-transition/.test(attr.name)
        )) {
          return false;
        }
      },
      onNodeDiscarded: (node) => {
        store_default.callHook("element.removed", node, el);
        if (node.__raxm) {
          store_default.removeComponent(node.__raxm);
        }
        morphChanges.removed.push(node);
      },
      onBeforeElChildrenUpdated: (node) => {
      },
      onBeforeElUpdated: (from, to) => {
        if (from.isEqualNode(to)) {
          return false;
        }
        store_default.callHook("element.updating", from, to, el);
        if (from.hasAttribute(`${PREFIX_REGEX}model`) && from.tagName.toUpperCase() === "SELECT") {
          to.selectedIndex = -1;
        }
        let fromDirectives = getDirectives(from);
        if (fromDirectives.has("ignore") || from.__raxm_ignore === true || from.__raxm_ignore_self === true) {
          if (fromDirectives.has("ignore") && fromDirectives.get("ignore").modifiers.includes("self") || from.__raxm_ignore_self === true) {
            from.skipElUpdatingButStillUpdateChildren = true;
          } else {
            return false;
          }
        }
        if (dom_default.isComponentRootEl(from) && from.getAttribute(`${PREFIX_DISPLAY}id`) !== id)
          return false;
        if (dom_default.isComponentRootEl(from))
          to.__raxm = el;
        alpinifyElementsForMorphdom(from, to);
      },
      onElUpdated: (node) => {
        morphChanges.changed.push(node);
        store_default.callHook("element.updated", node, el);
      },
      onNodeAdded: (node) => {
        const closestComponentId = dom_default.closestRoot(node).getAttribute(`${PREFIX_REGEX}id`);
        if (closestComponentId === id) {
          if (node_initializer_default.initialize(node, el) === false) {
            return false;
          }
        } else if (dom_default.isComponentRootEl(node)) {
          store_default.addComponent(new Component(node, el.connection));
          node.skipAddingChildren = true;
        }
        morphChanges.added.push(node);
      }
    });
    window.skipShow = false;
    function isntElement(el2) {
      return typeof el2.hasAttribute !== "function";
    }
    function isComponentRootEl(el2) {
      return el2.hasAttribute(PREFIX_DISPLAY);
    }
  }

  // js/features/supportMorphDom.js
  on2("effects", (component, html) => {
    if (!html)
      return;
    queueMicrotask(() => {
      morph(component, html);
    });
  });

  // js/directives/axm-confirm.js
  directive("confirm", ({ el, directive: directive2 }) => {
    let message = directive2.expression;
    let shouldPrompt = directive2.modifiers.includes("prompt");
    message = message.replaceAll("\\n", "\n");
    if (message === "")
      message = "Are you sure?";
    el.__raxm_confirm = (action) => {
      if (shouldPrompt) {
        let [question, expected] = message.split("|");
        if (!expected) {
          console.warn("Raxm: Must provide expectation with axm:confirm.prompt");
        } else {
          let input = prompt(question);
          if (input === expected) {
            action();
          }
        }
      } else {
        if (confirm(message))
          action();
      }
    };
  });

  // js/directives/shared.js
  function toggleBooleanStateDirective(el, directive2, isTruthy, cachedDisplay = null) {
    isTruthy = directive2.modifiers.includes("remove") ? !isTruthy : isTruthy;
    if (directive2.modifiers.includes("class")) {
      let classes = directive2.expression.split(" ");
      if (isTruthy) {
        el.classList.add(...classes);
      } else {
        el.classList.remove(...classes);
      }
    } else if (directive2.modifiers.includes("attr")) {
      if (isTruthy) {
        el.setAttribute(directive2.expression, true);
      } else {
        el.removeAttribute(directive2.expression);
      }
    } else {
      let cache2 = cachedDisplay ?? window.getComputedStyle(el, null).getPropertyValue("display");
      let display = ["inline", "block", "table", "flex", "grid", "inline-flex"].filter((i) => directive2.modifiers.includes(i))[0] || "inline-block";
      display = directive2.modifiers.includes("remove") ? cache2 : display;
      el.style.display = isTruthy ? display : "none";
    }
  }

  // js/directives/axm-offline.js
  var offlineHandlers = /* @__PURE__ */ new Set();
  var onlineHandlers = /* @__PURE__ */ new Set();
  window.addEventListener("offline", () => offlineHandlers.forEach((i) => i()));
  window.addEventListener("online", () => onlineHandlers.forEach((i) => i()));
  directive("offline", ({ el, directive: directive2, cleanup: cleanup2 }) => {
    let setOffline = () => toggleBooleanStateDirective(el, directive2, true);
    let setOnline = () => toggleBooleanStateDirective(el, directive2, false);
    offlineHandlers.add(setOffline);
    onlineHandlers.add(setOnline);
    cleanup2(() => {
      offlineHandlers.delete(setOffline);
      onlineHandlers.delete(setOnline);
    });
  });

  // js/directives/axm-loading.js
  directive("loading", ({ el, directive: directive2, component }) => {
    let targets = getTargets(el);
    let [delay, abortDelay] = applyDelay(directive2);
    const start3 = () => delay(() => toggleBooleanStateDirective(el, directive2, true));
    const end = () => abortDelay(() => toggleBooleanStateDirective(el, directive2, false));
    whenTargetsArePartOfRequest(component, targets, [start3, end]);
    whenTargetsArePartOfFileUpload(component, targets, [start3, end]);
  });
  function applyDelay(directive2) {
    if (!directive2.modifiers.includes("delay") || directive2.modifiers.includes("none"))
      return [(i) => i(), (i) => i()];
    let duration = 200;
    let delayModifiers = {
      shortest: 50,
      shorter: 100,
      short: 150,
      default: 200,
      long: 300,
      longer: 500,
      longest: 1e3
    };
    if (Object.keys(delayModifiers).includes(directive2.modifiers[0])) {
      duration = delayModifiers[directive2.modifiers[0]];
    }
    let timeout2;
    let started = false;
    return [
      (callback) => {
        timeout2 = setTimeout(() => {
          callback();
          started = true;
        }, duration);
      },
      (callback) => {
        if (started) {
          callback();
        } else {
          clearTimeout(timeout2);
        }
      }
    ];
  }
  function whenTargetsArePartOfRequest(iComponent, targets, [startLoading, endLoading]) {
    const hookCallbackStart = (message, component) => {
      if (iComponent !== component)
        return;
      const payload = message.updateQueue[0].payload;
      if (targets.length > 0 && !containsTargets(payload, targets))
        return;
      startLoading();
    };
    const hookCallbackEnd = () => {
      endLoading();
    };
    store_default.registerHook("message.sent", hookCallbackStart);
    store_default.registerHook("message.failed", hookCallbackEnd);
    store_default.registerHook("message.received", hookCallbackEnd);
    store_default.registerHook("element.removed", hookCallbackEnd);
  }
  function whenTargetsArePartOfFileUpload(component, targets, [startLoading, endLoading]) {
    let eventMismatch = (e) => {
      let { id, property: property2 } = e.detail;
      if (id !== component.id)
        return true;
      if (targets.length > 0 && !targets.map((i) => i.target).includes(property2))
        return true;
      return false;
    };
    window.addEventListener("raxm-upload-start", (e) => {
      if (eventMismatch(e))
        return;
      startLoading();
    });
    window.addEventListener("raxm-upload-finish", (e) => {
      if (eventMismatch(e))
        return;
      endLoading();
    });
    window.addEventListener("raxm-upload-error", (e) => {
      if (eventMismatch(e))
        return;
      endLoading();
    });
  }
  function containsTargets(payload, targets) {
    let { name, method, params } = payload;
    let target = targets.find(({ target: target2, tparams }) => {
      if (tparams) {
        return target2 === method && tparams === quickHash(JSON.stringify(params));
      }
      return name === target2 || method === target2;
    });
    return target !== void 0;
  }
  function getTargets(el) {
    let directives = getDirectives(el);
    let targets = [];
    if (directives.has("target")) {
      let directive2 = directives.get("target");
      let raw = directive2.expression;
      if (raw.includes("(") && raw.includes(")")) {
        targets.push({
          target: directive2.method,
          params: quickHash(JSON.stringify(directive2.params))
        });
      } else if (raw.includes(",")) {
        raw.split(",").map((i) => i.trim()).forEach((target) => {
          targets.push({ target });
        });
      } else {
        targets.push({ target: raw });
      }
    } else {
      let nonActionOrModelRaxmDirectives = [
        "init",
        "dirty",
        "offline",
        "target",
        "loading",
        "poll",
        "ignore",
        "key",
        "id"
      ];
      targets = directives.all().filter(
        (i) => !nonActionOrModelRaxmDirectives.includes(i.value) && i.expression.split("(")[0]
      ).map((i) => ({ target: i.expression.split("(")[0] }));
    }
    return targets;
  }
  function quickHash(subject) {
    return btoa(encodeURIComponent(subject));
  }

  // js/directives/axm-ignore.js
  directive("ignore", ({ el, directive: directive2 }) => {
    if (directive2.modifiers.includes("self")) {
      el.__raxm_ignore_self = true;
    } else {
      el.__raxm_ignore = true;
    }
  });

  // js/directives/axm-dirty.js
  var refreshDirtyStatesByComponent = new WeakBag();
  store_default.registerHook("component.initialized", (directive2, el, component) => {
    setTimeout(() => {
      refreshDirtyStatesByComponent.each(component, (i) => i(false));
    });
  });
  directive("dirty", ({ el, directive: directive2, component }) => {
    let targets = dirtyTargets(el);
    let oldIsDirty = false;
    let initialDisplay = el.style.display;
    let refreshDirtyState = (isDirty2) => {
      toggleBooleanStateDirective(el, directive2, isDirty2, initialDisplay);
      oldIsDirty = isDirty2;
    };
    refreshDirtyStatesByComponent.add(component, refreshDirtyState);
    let isDirty = false;
    for (let i = 0; i < targets.length; i++) {
      if (isDirty)
        break;
      let target = targets[i];
      isDirty = dom_default.valueFromInput(el, component) != component.get(target);
    }
    if (oldIsDirty !== isDirty) {
      refreshDirtyState(isDirty);
    }
    oldIsDirty = isDirty;
  });
  function dirtyTargets(el) {
    let directives = getDirectives(el);
    let targets = [];
    if (directives.has("model")) {
      targets.push(directives.get("model").expression);
    }
    if (directives.has("target")) {
      targets = targets.concat(
        directives.get("target").expression.split(",").map((s) => s.trim())
      );
    }
    return targets;
  }

  // js/directives/axm-model.js
  directive("model", ({ el, directive: directive2, component, cleanup: cleanup2 }) => {
    let { expression, modifiers } = directive2;
    if (!expression) {
      return console.warn(`Raxm: [${PREFIX_DISPLAY}model] is missing a value.`, el);
    }
    if (componentIsMissingProperty(component, expression)) {
      return console.warn(`Raxm: [${PREFIX_DISPLAY}model="` + expression + `"] property does not exist on component: [` + component.name + `]`, el);
    }
    if (el.type && el.type.toLowerCase() === "file") {
      return handleFileUpload(el, expression, component, cleanup2);
    }
    dom_default.setInputValueFromModel(el, component);
    attachModelListener(el, directive2, component);
  });
  function attachModelListener(el, directive2, component) {
    let { expression, modifiers } = directive2;
    el.isRaxmModel = true;
    let isLive = modifiers.includes("live");
    let isLazy = modifiers.includes("lazy");
    let isDefer = modifiers.includes("defer");
    let isDebounced = modifiers.includes("debounce");
    store_default.callHook("interceptRaxmModelAttachListener", directive2, el, component, expression);
    const event = el.tagName === "SELECT" || ["checkbox", "radio"].includes(el.type) || isLazy ? "change" : "input";
    const debounceIf = (condition, callback, time) => {
      return condition ? modelSyncDebounce(callback, time) : callback;
    };
    let handler = debounceIf(dom_default.isTextInput(el) && !isDebounced && !isLazy, (e) => {
      let model = directive2.value;
      let el2 = e.target;
      let value2 = e instanceof CustomEvent && typeof e.detail != "undefined" && typeof window.document.documentMode == "undefined" ? e.detail ?? e.target.value : dom_default.valueFromInput(el2, component);
      if (isDefer) {
        addAction(component, new model_default(model, value2, el2));
      } else {
        addAction(component, new model_default(model, value2, el2));
      }
    }, directive2.durationOr(150));
    el.addEventListener(event, handler);
    component.addListenerForTeardown(() => {
      el.removeEventListener(event, handler);
    });
    let isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    isSafari && el.addEventListener("animationstart", (e) => {
      if (e.animationName !== "raxmautofill")
        return;
      e.target.dispatchEvent(new Event("change", { bubbles: true }));
      e.target.dispatchEvent(new Event("input", { bubbles: true }));
    });
  }
  function componentIsMissingProperty(component, property2) {
    if (property2.startsWith("$parent")) {
      let parent = closestComponent(component.el.parentElement, false);
      if (!parent)
        return true;
      return componentIsMissingProperty(parent, property2.split("$parent.")[1]);
    }
    let baseProperty = property2.split(".")[0];
    return !Object.keys(component.serverMemo.data).includes(baseProperty);
  }

  // js/directives/axm-init.js
  directive("init", ({ el, directive: directive2, component }) => {
    const method = directive2.value ? directive2.method : "$refresh";
    addAction(component, new method_default(method, directive2.params, el));
  });

  // js/directives/axm-poll.js
  directive("poll", ({ el, directive: directive2, component }) => {
    let interval = extractDurationFrom(directive2.modifiers, 2e3);
    let { start: start3, pauseWhile, throttleWhile, stopWhen } = poll(() => {
      triggerComponentRequest(el, directive2, component);
    }, interval);
    start3();
    throttleWhile(() => theTabIsInTheBackground() && theDirectiveIsMissingKeepAlive(directive2));
    pauseWhile(() => theDirectiveHasVisible(directive2) && theElementIsNotInTheViewport(el));
    pauseWhile(() => theDirectiveIsOffTheElement(el));
    pauseWhile(() => raxmIsOffline());
    stopWhen(() => theElementIsDisconnected(el));
  });
  function triggerComponentRequest(el, directive2, component) {
    const method = directive2.method || "$refresh";
    addAction(component, new method_default(method, directive2.params, el));
  }
  function poll(callback, interval = 2e3) {
    let pauseConditions = [];
    let throttleConditions = [];
    let stopConditions = [];
    return {
      start() {
        let clear = syncronizedInterval(interval, () => {
          if (stopConditions.some((i) => i()))
            return clear();
          if (pauseConditions.some((i) => i()))
            return;
          if (throttleConditions.some((i) => i()) && Math.random() < 0.95)
            return;
          callback();
        });
      },
      pauseWhile(condition) {
        pauseConditions.push(condition);
      },
      throttleWhile(condition) {
        throttleConditions.push(condition);
      },
      stopWhen(condition) {
        stopConditions.push(condition);
      }
    };
  }
  var clocks = [];
  function syncronizedInterval(ms, callback) {
    if (!clocks[ms]) {
      let clock = {
        timer: setInterval(() => clock.callbacks.forEach((i) => i()), ms),
        callbacks: /* @__PURE__ */ new Set()
      };
      clocks[ms] = clock;
    }
    clocks[ms].callbacks.add(callback);
    return () => {
      clocks[ms].callbacks.delete(callback);
      if (clocks[ms].callbacks.size === 0) {
        clearInterval(clocks[ms].timer);
        delete clocks[ms];
      }
    };
  }
  var isOffline = false;
  window.addEventListener("offline", () => isOffline = true);
  window.addEventListener("online", () => isOffline = false);
  function raxmIsOffline() {
    return isOffline;
  }
  var inBackground = false;
  document.addEventListener("visibilitychange", () => {
    inBackground = document.hidden;
  }, false);
  function theTabIsInTheBackground() {
    return inBackground;
  }
  function theDirectiveIsOffTheElement(el) {
    return !getDirectives(el).has("poll");
  }
  function theDirectiveIsMissingKeepAlive(directive2) {
    return !directive2.modifiers.includes("keep-alive");
  }
  function theDirectiveHasVisible(directive2) {
    return directive2.modifiers.includes("visible");
  }
  function theElementIsNotInTheViewport(el) {
    let bounding = el.getBoundingClientRect();
    return !(bounding.top < (window.innerHeight || document.documentElement.clientHeight) && bounding.left < (window.innerWidth || document.documentElement.clientWidth) && bounding.bottom > 0 && bounding.right > 0);
  }
  function theElementIsDisconnected(el) {
    return el.isConnected === false;
  }
  function extractDurationFrom(modifiers, defaultDuration) {
    let durationInMilliSeconds;
    let durationInMilliSecondsString = modifiers.find((mod) => mod.match(/([0-9]+)ms/));
    let durationInSecondsString = modifiers.find((mod) => mod.match(/([0-9]+)s/));
    if (durationInMilliSecondsString) {
      durationInMilliSeconds = Number(durationInMilliSecondsString.replace("ms", ""));
    } else if (durationInSecondsString) {
      durationInMilliSeconds = Number(durationInSecondsString.replace("s", "")) * 1e3;
    }
    return durationInMilliSeconds || defaultDuration;
  }

  // js/index.js
  var Raxm = {
    directive,
    start,
    stop,
    rescan,
    find,
    first,
    getByName,
    all,
    on,
    trigger,
    hook
  };
  if (window.Raxm)
    console.warn("Detected multiple instances of Raxm running");
  if (window.Alpine)
    console.warn("Detected multiple instances of Alpine running");
  if (window.Raxm === void 0) {
    document.addEventListener("DOMContentLoaded", () => {
      window.Raxm = Raxm;
      Raxm.start();
    });
  }
})();
