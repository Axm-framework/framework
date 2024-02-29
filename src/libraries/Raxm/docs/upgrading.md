## Automated upgrade tool

To save you time upgrading, we've included an Artisan command to automate as many parts of the upgrade process as possible.

After [installing Raxm version 3](/docs/upgrading#update-raxm-to-version-3), run the following command and you will receive prompts to upgrade each breaking change automatically:

```shell
php axm raxm:upgrade
```

Although the above command can upgrade much of your application, the only way to ensure a complete upgrade is to follow the step-by-step guide on this page.

> [!tip] Hire us to upgrade your app instead
> If you have a large Raxm application or just don't want to deal with upgrading from version 2 to version 3, you can hire us to handle it for you. [Learn more about our upgrade service here.](/jumpstart)

## Upgrade PHP

Raxm now requires that your application is running on PHP version 8.1 or greater.

## Update Raxm to version 3

Run the following composer command to upgrade your application's Raxm dependency from version 2 to 3:

```shell
composer require raxm/raxm "^3.0"
```

> [!warning] Raxm 3 package compatibility
> Most of the major third-party Raxm packages either currently support Raxm 3 or are working on supporting it soon. However, there will inevitably be packages that take longer to release support for Raxm 3.

## Clear the view cache

Run the following Artisan command from your application's root directory to clear any cached/compiled Blade views and force Raxm to re-compile them to be Raxm 3 compatible:

```shell
php axm view:clear
```

## Merge new configuration

Raxm 3 has changed multiple configuration options. If your application has a published configuration file (`config/raxm.php`), you will need to update it to account for the following changes.

### New configuration

The following configuration keys have been introduced in version 3:

```php
'legacy_model_binding' => false,

'inject_assets' => true,

'inject_morph_markers' => true,

'navigate' => false,
```

You can reference [Raxm's new configuration file on GitHub](https://github.com/raxm/raxm/blob/master/config/raxm.php) for additional option descriptions and copy-pastable code.

### Changed configuration

The following configuration items have been updated with new default values:

#### New class namespace

Raxm's default `class_namespace` has changed from `App\Http\Raxm` to `App\Raxm`. You are welcome to keep the old namespace configuration value; however, if you choose to update your configuration to the new namespace, you will have to move your Raxm components to `app/Raxm`:

```php
'class_namespace' => 'App\\Http\\Raxm', // [tl! remove]
'class_namespace' => 'App\\Raxm', // [tl! add]
```

#### New layout view path

When rendering full-page components in version 2, Raxm would use `resources/views/layouts/app.blade.php` as the default layout Blade component.

Because of a growing community preference for anonymous Blade components, Raxm 3 has changed the default location to: `resources/views/components/layouts/app.blade.php`.

```php
'layout' => 'layouts.app', // [tl! remove]
'layout' => 'components.layouts.app', // [tl! add]
```

### Removed configuration

Raxm no longer recognizes the following configuration items.

#### `app_url`

If your application is served under a non-root URI, in Raxm 2 you could use the `app_url` configuration option to configure the URL Raxm uses to make AJAX requests to.

In this case, we've found a string configuration to be too rigid. Therefore, Raxm 3 has chosen to use runtime configuration instead. You can reference our documentation on [configuring Raxm's update endpoint](/docs/installation#configuring-raxms-update-endpoint) for more information.

#### `asset_url`

In Raxm 2, if your application was served under a non-root URI, you would use the `asset_url` configuration option to configure the base URL that Raxm uses to serve its JavaScript assets.

Raxm 3 has instead chosen a runtime configuration strategy. You can reference our documentation on [configuring Raxm's script asset endpoint](/docs/installation#customizing-the-asset-url) for more information.

#### `middleware_group`

Because Raxm now exposes a more flexible way to customize its update endpoint, the `middleware_group` configuration option has been removed.

You can reference our documentation on [customizing Raxm's update endpoint](/docs/installation#configuring-raxms-update-endpoint) for more information on applying custom middleware to Raxm requests.

#### `manifest_path`

Raxm 3 no longer uses a manifest file for component autoloading. Therefore, the `manifest_path` configuration is no longer necessary.

#### `back_button_cache`

Because Raxm 3 now offers an [SPA experience for your application using `axm:navigate`](/docs/navigate), the `back_button_cache` configuration is no longer necessary.

## Raxm app namespace

In version 2, Raxm components were generated and recognized automatically under the `App\Http\Raxm` namespace.

Raxm 3 has changed this default to: `App\Raxm`.

You can either move all of your components to the new location or add the following configuration to your application's `config/raxm.php` configuration file:

```php
'class_namespace' => 'App\\Http\\Raxm',
```

## Page component layout view

When rendering Raxm components as full pages using a syntax like the following:

```php
Route::get('/posts', ShowPosts::class);
```

The Blade layout file used by Raxm to render the component has changed from `resources/views/layouts/app.blade.php` to `resources/views/components/layouts/app.blade.php`:

```shell
resources/views/layouts/app.blade.php #[tl! remove]
resources/views/components/layouts/app.blade.php #[tl! add]
```

You can either move your layout file to the new location or apply the following configuration inside your application's `config/raxm.php` configuration file:

```php
'layout' => 'layouts.app',
```

For more information, check out the documentation on [creating and using a page-component layout](/docs/components#layout-files).

## Eloquent model binding

Raxm 2 supported `axm:model` binding directly to Eloquent model properties. For example, the following was a common pattern:

```php
public Post $post;

protected $rules = [
    'post.title' => 'required',
    'post.description' => 'required',
];
```

```html
<input axm:model="post.title" /> <input axm:model="post.description" />
```

In Raxm 3, binding directly to Eloquent models has been disabled in favor of using individual properties, or extracting [Form Objects](/docs/forms#extracting-a-form-object).

However, because this behavior is so heavily relied upon in Raxm applications, version 3 maintains support for this behavior via a configuration item in `config/raxm.php`:

```php
'legacy_model_binding' => true,
```

By setting `legacy_model_binding` to `true`, Raxm will handle Eloquent model properties exactly as it did in version 2.

## AlpineJS

Raxm 3 ships with [AlpineJS](https://alpinejs.dev) by default.

If you manually include Alpine in your Raxm application, you will need to remove it, so that Raxm's built-in version doesn't conflict.

### Including Alpine via a script tag

If you include Alpine into your application via a script tag like the following, you can remove it entirely and Raxm will load its internal version instead:

```html
<script
  defer
  src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"
></script>
<!-- [tl! remove] -->
```

### Including plugins via a script tag

Raxm 3 now ships with the following Alpine plugins out-of-the-box:

- [Collapse](https://alpinejs.dev/plugins/collapse)
- [Focus](https://alpinejs.dev/plugins/focus)
- [Intersect](https://alpinejs.dev/plugins/intersect)
- [Mask](https://alpinejs.dev/plugins/mask)
- [Morph](https://alpinejs.dev/plugins/morph)
- [Persist](https://alpinejs.dev/plugins/persist)

If you have already included any of these in your application via `<script>` tags like below, you can remove them along with Alpine's core:

```html
<script
  defer
  src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"
></script>
<!-- [tl! remove:1] -->
<!-- ... -->
```

### Accessing the Alpine global via a script tag

If you are currently accessing the `Alpine` global object from a script tag like so:

```html
<script>
  document.addEventListener('alpine:init', () => {
      Alpine.data(...)
  })
</script>
```

You may continue to do so, as Raxm internally includes and registers Alpine's global object like before.

### Including via JS bundle

If you have included Alpine and any relevant plugins via NPM into your applications JavaScript bundle like so:

```js
// Warning: this is a snippet of the Raxm 2 approach to including Alpine

import Alpine from "alpinejs";
import intersect from "@alpinejs/intersect";

Alpine.plugin(intersect);

Alpine.start();
```

You can remove them entirely, because Raxm includes Alpine and many popular Alpine plugins by default.

#### Accessing Alpine via JS bundle

If you are registering custom Alpine plugins or components inside your application's JavaScript bundle like so:

```js
// Warning: this is a snippet of the Raxm 2 approach to including Alpine

import Alpine from "alpinejs";
import customPlugin from "./plugins/custom-plugin";

Alpine.plugin(customPlugin);

Alpine.start();
```

You can still accomplish this by importing the Raxm core ESM module into your bundle and accessing `Alpine` from there.

To import Raxm into your bundle, you must first disable Raxm's normal JavaScript injection and provide the necessary configuration to Raxm by replacing `@raxmScripts` with `@raxmScriptConfig` in your application's primary layout:

```html
    <!-- ... -->

    @raxmScripts <!-- [tl! remove] -->
    @raxmScriptConfig <!-- [tl! add] -->
</body>
```

Now, you can import `Alpine` and `Raxm` into your application's bundle like so:

```js
import { Raxm, Alpine } from "../../vendor/raxm/raxm/dist/raxm.esm";
import customPlugin from "./plugins/custom-plugin";

Alpine.plugin(customPlugin);

Raxm.start();
```

Notice you no longer need to call `Alpine.start()`. Raxm will start Alpine automatically.

For more information, please consult our documentation on [manually bundling Raxm's JavaScript](/docs/installation#manually-bundling-raxm-and-alpine).

## `axm:model`

In Raxm 3, `axm:model` is "deferred" by default (instead of by `axm:model.defer`). To achieve the same behavior as `axm:model` from Raxm 2, you must use `axm:model.live`.

Below is a list of the necessary substitutions you will need to make in your templates to keep your application's behavior consistent:

```html
<input axm:model="..." />
<!-- [tl! remove] -->
<input axm:model.live="..." />
<!-- [tl! add] -->

<input axm:model.defer="..." />
<!-- [tl! remove] -->
<input axm:model="..." />
<!-- [tl! add] -->

<input axm:model.lazy="..." />
<!-- [tl! remove] -->
<input axm:model.blur="..." />
<!-- [tl! add] -->
```

## `@entangle`

Similar to the changes to `axm:model`, Raxm 3 defers all data binding by default. To match this behavior, `@entangle` has been updated as well.

To keep your application running as expected, make the following `@entangle` substitutions:

```html
@entangle(...)
<!-- [tl! remove] -->
@entangle(...).live
<!-- [tl! add] -->

@entangle(...).defer
<!-- [tl! remove] -->
@entangle(...)
<!-- [tl! add] -->
```

## Events

In Raxm 2, Raxm had two different PHP methods for triggering events:

- `emit()`
- `dispatchBrowserEvent()`

Raxm 3 has unified these two methods into a single method:

- `dispatch()`

Here is a basic example of dispatching and listening for an event in Raxm 3:

```php
// Dispatching...
class CreatePost extends Component
{
    public Post $post;

    public function save()
    {
        $this->dispatch('post-created', postId: $this->post->id);
    }
}

// Listening...
class Dashboard extends Component
{
    #[On('post-created')]
    public function postAdded($postId)
    {
        //
    }
}
```

The three main changes from Raxm 2 are:

1. `emit()` has been renamed to `dispatch()`
1. `dispatchBrowserEvent()` has been renamed to `dispatch()`
1. All event parameters must be named

For more information, check out the new [events documentation page](/docs/events).

Here are the "find and replace" differences that should be applied to your application:

```php
$this->emit('post-created'); // [tl! remove]
$this->dispatch('post-created'); // [tl! add]

$this->emitTo('foo', 'post-created'); // [tl! remove]
$this->dispatch('post-created')->to('foo'); // [tl! add]

$this->emitSelf('post-created'); // [tl! remove]
$this->dispatch('post-created')->self(); // [tl! add]

$this->emit('post-created', $post->id); // [tl! remove]
$this->dispatch('post-created', postId: $post->id); // [tl! add]

$this->dispatchBrowserEvent('post-created'); // [tl! remove]
$this->dispatch('post-created'); // [tl! add]

$this->dispatchBrowserEvent('post-created', ['postId' => $post->id]); // [tl! remove]
$this->dispatch('post-created', postId: $post->id); // [tl! add]
```

```html
<button axm:click="$emit('post-created')">...</button>
<!-- [tl! remove] -->
<button axm:click="$dispatch('post-created')">...</button>
<!-- [tl! add] -->

<button axm:click="$emit('post-created', 1)">...</button>
<!-- [tl! remove] -->
<button axm:click="$dispatch('post-created', { postId: 1 })">...</button>
<!-- [tl! add] -->

<button axm:click="$emitTo('foo', post-created', 1)">...</button>
<!-- [tl! remove] -->
<button axm:click="$dispatchTo('foo', 'post-created', { postId: 1 })">
  ...
</button>
<!-- [tl! add] -->

<button x-on:click="$raxm.emit('post-created', 1)">...</button>
<!-- [tl! remove] -->
<button x-on:click="$dispatch('post-created', { postId: 1 })">...</button>
<!-- [tl! add] -->
```

### `emitUp()`

The concept of `emitUp` has been removed entirely. Events are now dispatched using browser events and therefore will "bubble up" by default.

You can remove any instances of `$this->emitUp(...)` or `$emitUp(...)` from your components.

### Testing events

Raxm has also changed event assertions to match the new unified terminology regarding dispatching events:

```php
Raxm::test(Component::class)->assertEmitted('post-created'); // [tl! remove]
Raxm::test(Component::class)->assertDispatched('post-created'); // [tl! add]

Raxm::test(Component::class)->assertEmittedTo(Foo::class, 'post-created'); // [tl! remove]
Raxm::test(Component::class)->assertDispatchedTo(Foo:class, 'post-created'); // [tl! add]

Raxm::test(Component::class)->assertNotEmitted('post-created'); // [tl! remove]
Raxm::test(Component::class)->assertNotDispatched('post-created'); // [tl! add]

Raxm::test(Component::class)->assertEmittedUp() // [tl! remove]
```

### URL query string

In previous Raxm versions, if you bound a property to the URL's query string, the property value would always be present in the query string, unless you used the `except` option.

In Raxm 3, all properties bound to the query string will only show up if their value has been changed after the page load. This default removes the need for the `except` option:

```php
public $search = '';

protected $queryString = [
    'search' => ['except' => ''], // [tl! remove]
    'search', // [tl! add]
];
```

If you'd like to revert back to the Raxm 2 behavior of always showing a property in the query string no matter its value, you can use the `keep` option:

```php
public $search = '';

protected $queryString = [
    'search' => ['keep' => true], // [tl! highlight]
];
```

## Pagination

The pagination system has been updated in Raxm 3 to better support multiple paginators within the same component.

### Update published pagination views

If you've published Raxm's pagination views, you can reference the new ones in the [pagination directory on GitHub](https://github.com/raxm/raxm/tree/master/src/Features/SupportPagination/views) and update your application accordingly.

### Accessing `$this->page` directly

Because Raxm now supports multiple paginators per component, it has removed the `$page` property from the component class and replaced it with a `$paginators` property that stores an array of paginators:

```php
$this->page = 2; // [tl! remove]
$this->paginators['page'] = 2; // [tl! add]
```

However, it is recommended that you use the provided `getPage` and `setPage` methods to modify and access the current page:

```php
// Getter...
$this->getPage();

// Setter...
$this->setPage(2);
```

### `axm:click.prefetch`

Raxm's prefetching feature (`axm:click.prefetch`) has been removed entirely. If you depended on this feature, your application will still work, it will just be slightly less performant in the instances where you were previously benefiting from `.prefetch`.

```html
<button axm:click.prefetch="">
  <!-- [tl! remove] -->
  <button axm:click="..."><!-- [tl! add] --></button>
</button>
```

## Component class changes

The following changes have been made to Raxm's base `App\Raxm` class that your application's components may have relied on.

### The component `$id` property

If you accessed the component's ID directly via `$this->id`, you should instead use `$this->getId()`:

```php
$this->id; // [tl! remove]

$this->getId(); // [tl! add]
```

### Duplicate method and property names

PHP allows you to use the same name for both a class property and method. In Raxm 3, this will cause problems when calling methods from the frontend via `axm:click`.

It is strongly recommended that you use distinct names for all public methods and properties in a component:

```php
public $search = ''; // [tl! remove]

public function search() {
    // ...
}
```

```php
public $query = ''; // [tl! add]

public function search() {
    // ...
}
```

## JavaScript API changes

### `raxm:load`

In previous versions of Raxm, you could listen for the `raxm:load` event to execute JavaScript code immediately before Raxm initialized the page.

In Raxm 3, that event name has been changed to `raxm:init` to match Alpine's `alpine:init`:

```js
document.addEventListener('raxm:load', () => {...}) // [tl! remove]
document.addEventListener('raxm:init', () => {...}) // [tl! add]
```

### Page expired hook

In version 2, Raxm exposed a dedicated JavaScript method for customizing the page expiration behavior: `Raxm.onPageExpired()`. This method has been removed in favor of using the more powerful `request` hooks directly:

```js
Raxm.onPageExpired(() => {...}) // [tl! remove]

Raxm.hook('request', ({ fail }) => { // [tl! add:8]
    fail(({ status, preventDefault }) => {
        if (status === 419) {
            preventDefault()

            confirm('Your custom page expiration behavior...')
        }
    })
})
```

### New lifecycle hooks

Many of Raxm's internal JavaScript lifecycle hooks have been changed in Raxm 3.

Here is a comparison of the old hooks and their new syntaxes for you to find/replace in your application:

```js
Raxm.hook("component.initialized", (component) => {}); // [tl! remove]
Raxm.hook("component.init", ({ component, cleanup }) => {}); // [tl! add]

Raxm.hook("element.initialized", (el, component) => {}); // [tl! remove]
Raxm.hook("element.init", ({ el, component }) => {}); // [tl! add]

Raxm.hook("element.updating", (fromEl, toEl, component) => {}); // [tl! remove]
Raxm.hook("morph.updating", ({ el, toEl, component }) => {}); // [tl! add]

Raxm.hook("element.updated", (el, component) => {}); // [tl! remove]
Raxm.hook("morph.updated", ({ el, component }) => {}); // [tl! add]

Raxm.hook("element.removed", (el, component) => {}); // [tl! remove]
Raxm.hook("morph.removed", ({ el, component }) => {}); // [tl! add]

Raxm.hook("message.sent", (message, component) => {}); // [tl! remove]
Raxm.hook("message.failed", (message, component) => {}); // [tl! remove]
Raxm.hook("message.received", (message, component) => {}); // [tl! remove]
Raxm.hook("message.processed", (message, component) => {}); // [tl! remove]

Raxm.hook("commit", ({ component, commit, respond, succeed, fail }) => {
  // [tl! add:14]
  // Equivalent of 'message.sent'

  succeed(({ snapshot, effect }) => {
    // Equivalent of 'message.received'

    queueMicrotask(() => {
      // Equivalent of 'message.processed'
    });
  });

  fail(() => {
    // Equivalent of 'message.failed'
  });
});
```

You may consult the new [JavaScript hook documentation](/docs/javascript) for a more thorough understanding of the new hook system.

## Localization

If your application uses a locale prefix in the URI such as `https://example.com/en/...`, Raxm 2 automatically preserved this URL prefix when making component updates via `https://example.com/en/raxm/update`.

Raxm 3 has stopped supporting this behavior automatically. Instead, you can override Raxm's update endpoint with any URI prefixes you need using `setUpdateRoute()`:

```php
Route::group(['prefix' => RaxmLocalization::setLocale()], function ()
{
    // Your other localized routes...

    Raxm::setUpdateRoute(function ($handle) {
        return Route::post('/raxm/update', $handle);
    });
});
```

For more information, please consult our documentation on [configuring Raxm's update endpoint](/docs/installation#configuring-raxms-update-endpoint).
