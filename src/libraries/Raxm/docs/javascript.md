Raxm provides plenty of JavaScript extension points for advanced users who want to use Raxm in deeper ways or extend Raxm's features with custom APIs.

## Global Raxm events

Raxm dispatches two helpful browser events for you to register any custom extension points:

```html
<script>
  document.addEventListener("raxm:init", () => {
    // Runs after Raxm is loaded but before it's initialized
    // on the page...
  });

  document.addEventListener("raxm:initialized", () => {
    // Runs immediately after Raxm has finished initializing
    // on the page...
  });
</script>
```

> [!info]
> It is often beneficial to register any [custom directives](#registering-custom-directives) or [lifecycle hooks](#javascript-hooks) inside of `raxm:init` so that they are available before Raxm begins initializing on the page.

## The `Raxm` global object

Raxm's global object is the best starting point for interacting with Raxm in JavaScript.

You can access the global `Raxm` JavaScript object on `window` from anywhere inside your client-side code.

It is often helpful to use `window.Raxm` inside a `raxm:init` event listener

### Accessing components

You can use the following methods to access specific Raxm components loaded on the current page:

```js
// Retrieve the $raxm object for the first component on the page...
let component = Raxm.first();

// Retrieve a given component's `$raxm` object by its ID...
let component = Raxm.find(id);

// Retrieve an array of component `$raxm` objects by name...
let components = Raxm.getByName(name);

// Retrieve $raxm objects for every component on the page...
let components = Raxm.all();
```

> [!info]
> Each of these methods returns a `$raxm` object representing the component's state in Raxm.
> <br><br>
> You can learn more about these objects in [the `$raxm` documentation](#the-wire-object).

### Interacting with events

In addition to dispatching and listening for events from individual components in PHP, the global `Raxm` object allows you interact with [Raxm's event system](/docs/events) from anywhere in your application:

```js
// Dispatch an event to any Raxm components listening...
Raxm.dispatch("post-created", { postId: 2 });

// Dispatch an event to a given Raxm component by name...
Raxm.dispatchTo("dashboard", "post-created", { postId: 2 });

// Listen for events dispatched from Raxm components...
Raxm.on("post-created", ({ postId }) => {
  // ...
});
```

### Accessing hooks

Raxm allows you to hook into various parts of its internal lifecycle using `Raxm.hook()`:

```js
// Register a callback to execute on a given internal Raxm hook...
Raxm.hook("component.init", ({ component, cleanup }) => {
  // ...
});
```

More information about Raxm's JavaScript hooks can be [found below](#javascript-hooks).

### Registering custom directives

Raxm allows you to register custom directives using `Raxm.directive()`.

Below is an example of a custom `axm:confirm` directive that uses JavaScript's `confirm()` dialog to confirm or cancel an action before it is sent to the server:

```html
<button axm:confirm="Are you sure?" axm:click="delete">Delete post</button>
```

Here is the implementation of `axm:confirm` using `Raxm.directive()`:

```js
Raxm.directive("confirm", ({ el, directive, component, cleanup }) => {
  let content = directive.expression;

  // The "directive" object gives you access to the parsed directive.
  // For example, here are its values for: axm:click.prevent="deletePost(1)"
  //
  // directive.raw = axm:click.prevent
  // directive.value = "click"
  // directive.modifiers = ['prevent']
  // directive.expression = "deletePost(1)"

  let onClick = (e) => {
    if (!confirm(content)) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  };

  el.addEventListener("click", onClick, { capture: true });

  // Register any cleanup code inside `cleanup()` in the case
  // where a Raxm component is removed from the DOM while
  // the page is still active.
  cleanup(() => {
    el.removeEventListener("click", onClick);
  });
});
```

### Controlling Raxm's initialization

In general, you shouldn't need to manually start or stop Raxm, however, if you find yourself needing this behavior, Raxm makes it available to you via the following methods:

```js
// Start Raxm on a page that doesn't have Raxm running...
Raxm.start();

// Stop Raxm and teardown its JavaScript runtime
// (remove event listeners and such)...
Raxm.stop();

// Force Raxm to scan the DOM for any components it may have missed...
Raxm.rescan();
```

## Object schemas

When extending Raxm's JavaScript system, it's important to understand the different objects you might encounter.

Here is an exhaustive reference of each of Raxm's relevant internal properties.

As a reminder, the average Raxm user may never interact with these. Most of these objects are available for Raxm's internal system or advanced users.

### The `$raxm` object

Given the following generic `Counter` component:

```php
<?php

namespace App\Raxm;

use App\Raxm;

class Counter extends Component
{
    public $count = 1;

    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return view('raxm.counter');
    }
}
```

Raxm exposes a JavaScript representation of the server-side component in the form of an object that is commonly referred to as `$raxm`:

```js
let $raxm = {
    // All component public properties are directly accessible on $raxm...
    count: 0,

    // All public methods are exposed and callable on $raxm...
    increment() { ... },

    // Access the `$raxm` object of the parent component if one exists...
    $parent,

    // Get the value of a property by name...
    // Usage: $raxm.$get('count')
    $get(name) { ... },

    // Set a property on the component by name...
    // Usage: $raxm.$set('count', 5)
    $set(name, value, live = true) { ... },

    // Toggle the value of a boolean property...
    $toggle(name, live = true) { ... },

    // Call the method
    // Usage: $raxm.$call('increment')
    $call(method, ...params) { ... },

    // Entangle the value of a Raxm property with a different,
    // arbitrary, Alpine property...
    // Usage: <div x-data="{ count: $raxm.$entangle('count') }">
    $entangle(name, live = false) { ... },

    // Watch the value of a property for changes...
    // Usage: Alpine.$watch('count', (value, old) => { ... })
    $watch(name, callback) { ... },

    // Refresh a component by sending a commit to the server
    // to re-render the HTML and swap it into the page...
    $refresh() { ... },

    // Identical to the above `$refresh`. Just a more technical name...
    $commit() { ... },

    // Listen for a an event dispatched from this component or its children...
    // Usage: $raxm.$on('post-created', () => { ... })
    $on(event, callback) { ... },

    // Dispatch an event from this component...
    // Usage: $raxm.$dispatch('post-created', { postId: 2 })
    $dispatch(event, params = {}) { ... },

    // Dispatch an event onto another component...
    // Usage: $raxm.$dispatchTo('dashboard', 'post-created', { postId: 2 })
    $dispatchTo(otherComponentName, event, params = {}) { ... },

    // Dispatch an event onto this component and no others...
    $dispatchSelf(event, params = {}) { ... },

    // A JS API to upload a file directly to component
    // rather than through `axm:model`...
    $upload(
        name, // The property name
        file, // The File JavaScript object
        finish = () => { ... }, // Runs when the upload is finished...
        error = () => { ... }, // Runs if an error is triggered mid-upload...
        progress = (event) => { // Runs as the upload progresses...
            event.detail.progress // An integer from 1-100...
        },
    ) { ... },

    // API to upload multiple files at the same time...
    $uploadMultiple(name, files, finish, error, progress) { },

    // Remove an upload after it's been temporarily uploaded but not saved...
    $removeUpload(name, tmpFilename, finish, error) { ... },

    // Retrieve the underlying "component" object...
    __instance() { ... },
}
```

You can learn more about `$raxm` in [Raxm's documentation on accessing properties in JavaScript](/docs/properties#accessing-properties-from-javascript).

### The `snapshot` object

Between each network request, Raxm serializes the PHP component into an object that can be consumed in JavaScript. This snapshot is used to unserialize the component back into a PHP object and therefore has mechanisms built in to prevent tampering:

```js
let snapshot = {
  // The serialized state of the component (public properties)...
  data: { count: 0 },

  // Long-standing information about the component...
  memo: {
    // The component's unique ID...
    id: "0qCY3ri9pzSSMIXPGg8F",

    // The component's name. Ex. <raxm:[name] />
    name: "counter",

    // The URI, method, and locale of the web page that the
    // component was originally loaded on. This is used
    // to re-apply any middleware from the original request
    // to subsequent component update requests (commits)...
    path: "/",
    method: "GET",
    locale: "en",

    // A list of any nested "child" components. Keyed by
    // internal template ID with the component ID as the values...
    children: [],

    // Weather or not this component was "lazy loaded"...
    lazyLoaded: false,

    // A list of any validation errors thrown during the
    // last request...
    errors: [],
  },

  // A securely encryped hash of this snapshot. This way,
  // if a malicous user tampers with the snapshot with
  // the goal of accessing un-owned resources on the server,
  // the checksum validation will fail and an error will
  // be thrown...
  checksum: "1bc274eea17a434e33d26bcaba4a247a4a7768bd286456a83ea6e9be2d18c1e7",
};
```

### The `component` object

Every component on a page has a corresponding component object behind the scenes keeping track of its state and exposing its underlying functionality. This is one layer deeper than `$raxm`. It is only meant for advanced usage.

Here's an actual component object for the above `Counter` component with descriptions of relevant properties in JS comments:

```js
let component = {
    // The root HTML element of the component...
    el: HTMLElement,

    // The unique ID of the component...
    id: '0qCY3ri9pzSSMIXPGg8F',

    // The component's "name" (<raxm:[name] />)...
    name: 'counter',

    // The latest "effects" object. Effects are "side-effects" from server
    // round-trips. These include redirects, file downloads, etc...
    effects: {},

    // The component's last-known server-side state...
    canonical: { count: 0 },

    // The component's mutable data object representing its
    // live client-side state...
    ephemeral: { count: 0 },

    // A reactive version of `this.ephemeral`. Changes to
    // this object will be picked up by AlpineJS expressions...
    reactive: Proxy,

    // A Proxy object that is typically used inside Alpine
    // expressions as `$raxm`. This is meant to provide a
    // friendly JS object interface for Raxm components...
    $axm: Proxy,

    // A list of any nested "child" components. Keyed by
    // internal template ID with the component ID as the values...
    children: [],

    // The last-known "snapshot" representation of this component.
    // Snapshots are taken from the server-side component and used
    // to re-create the PHP object on the backend...
    snapshot: {...},

    // The un-parsed version of the above snapshot. This is used to send back to the
    // server on the next roundtrip because JS parsing messes with PHP encoding
    // which often results in checksum mis-matches.
    snapshotEncoded: '{"data":{"count":0},"memo":{"id":"0qCY3ri9pzSSMIXPGg8F","name":"counter","path":"\/","method":"GET","children":[],"lazyLoaded":true,"errors":[],"locale":"en"},"checksum":"1bc274eea17a434e33d26bcaba4a247a4a7768bd286456a83ea6e9be2d18c1e7"}',
}
```

### The `commit` payload

When an action is performed on a Raxm component in the browser, a network request is triggered. That network request contains one or many components and various instructions for the server. Internally, these component network payloads are called "commits".

The term "commit" was chosen as a helpful way to think about Raxm's relationship between frontend and backend. A component is rendered and manipulated on the frontend until an action is performed that requires it to "commit" its state and updates to the backend.

You will recognize this schema from the payload in the network tab of your browser's DevTools, or [Raxm's JavaScript hooks](#javascript-hooks):

```js
let commit = {
    // Snapshot object...
    snapshot: { ... },

    // A key-value pair list of properties
    // to update on the server...
    updates: {},

    // An array of methods (with parameters) to call server-side...
    calls: [
        { method: 'increment', params: [] },
    ],
}
```

## JavaScript hooks

For advanced users, Raxm exposes its internal client-side "hook" system. You can use the following hooks to extend Raxm's functionality or gain more information about your Raxm application.

### Component initialization

Every time a new component is discovered by Raxm — whether on the initial page load or later on — the `component.init` event is triggered. You can hook into `component.init` to intercept or initialize anything related to the new component:

```js
Raxm.hook("component.init", ({ component, cleanup }) => {
  //
});
```

For more information, please consult the [documentation on the component object](#the-component-object).

### DOM element initialization

In addition to triggering an event when new components are initialized, Raxm triggers an event for each DOM element within a given Raxm component.

This can be used to provide custom Raxm HTML attributes within your application:

```js
Raxm.hook("element.init", ({ component, el }) => {
  //
});
```

### DOM Morph hooks

During the DOM morphing phase—which occurs after Raxm completes a network roundtrip—Raxm triggers a series of events for every element that is mutated.

```js
Raxm.hook("morph.updating", ({ el, component, toEl, skip, childrenOnly }) => {
  //
});

Raxm.hook("morph.updated", ({ el, component }) => {
  //
});

Raxm.hook("morph.removing", ({ el, component, skip }) => {
  //
});

Raxm.hook("morph.removed", ({ el, component }) => {
  //
});

Raxm.hook("morph.adding", ({ el, component }) => {
  //
});

Raxm.hook("morph.added", ({ el }) => {
  //
});
```

### Commit hooks

Because Raxm requests contain multiple components, _request_ is too broad of a term to refer to an individual component's request and response payload. Instead, internally, Raxm refers to component updates as _commits_ — in reference to _committing_ component state to the server.

These hooks expose `commit` objects. You can learn more about their schema by reading [the commit object documentation](#the-commit-payload).

#### Preparing commits

The `commit.prepare` hook will be triggered immediately before a request is sent to the server. This gives you a chance to add any last minute updates or actions to the outgoing request:

```js
Raxm.hook("commit.prepare", ({ component, commit }) => {
  // Runs before commit payloads are collected and sent to the server...
});
```

#### Intercepting commits

Every time a Raxm component is sent to the server, a _commit_ is made. To hook into the lifecycle and contents of an individual commit, Raxm exposes a `commit` hook.

This hook is extremely powerful as it provides methods for hooking into both the request and response of a Raxm commit:

```js
Raxm.hook("commit", ({ component, commit, respond, succeed, fail }) => {
  // Runs immediately before a commit's payload is sent to the server...

  respond(() => {
    // Runs after a response is received but before it's processed...
  });

  succeed(({ snapshot, effect }) => {
    // Runs after a successful response is received and processed
    // with a new snapshot and list of effects...
  });

  fail(() => {
    // Runs if some part of the request failed...
  });
});
```

## Request hooks

If you would like to instead hook into the entire HTTP request going and returning from the server, you can do so using the `request` hook:

```js
Raxm.hook("request", ({ uri, options, payload, respond, succeed, fail }) => {
  // Runs after commit payloads are compiled, but before a network request is sent...

  respond(({ status, response }) => {
    // Runs when the response is received...
    // "response" is the raw HTTP response object
    // before await response.text() is run...
  });

  succeed(({ status, json }) => {
    // Runs when the response is received...
    // "json" is the JSON response object...
  });

  fail(({ status, content, preventDefault }) => {
    // Runs when the response has an error status code...
    // "preventDefault" allows you to disable Raxm's
    // default error handling...
    // "content" is the raw response content...
  });
});
```

### Customizing page expiration behavior

If the default page expired dialog isn't suitable for your application, you can implement a custom solution using the `request` hook:

```html
<script>
  document.addEventListener("raxm:init", () => {
    Raxm.hook("request", ({ fail }) => {
      fail(({ status, preventDefault }) => {
        if (status === 419) {
          confirm("Your custom page expiration behavior...");

          preventDefault();
        }
      });
    });
  });
</script>
```

With the above code in your application, users will receive a custom dialog when their session has expired.
