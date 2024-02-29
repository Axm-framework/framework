Raxm is a Raxm package, so you will need to have a Raxm application up and running before you can install and use Raxm. If you need help setting up a new Raxm application, please see the [official Raxm documentation](https://laravel.com/docs/installation).

To install Raxm, open your terminal and navigate to your Raxm application directory, then run the following command:

```shell
composer require axm/raxm
```

That's it — really. If you want more customization options, keep reading. Otherwise, you can jump right into using Raxm.

## Publishing the configuration file

Raxm is "zero-config", meaning you can use it by following conventions, without any additional configuration. However, if needed, you can publish and customize Raxm's configuration file by running the following Artisan command:

```shell
php axm raxm:publish --config
```

This will create a new `raxm.php` file in your Raxm application's `config` directory.

## Manually including Raxm's frontend assets

By default, Raxm injects the JavaScript and CSS assets it needs into each page that includes a Raxm component.

If you want more control over this behavior, you can manually include the assets on a page using the following Blade directives:

```html
<html>
  <head>
    ... @raxmStyles
  </head>
  <body>
    ... @raxmScripts
  </body>
</html>
```

By including these assets manually on a page, Raxm knows not to inject the assets automatically.

> [!warning] AlpineJS is bundled with Raxm
> Because Alpine is bundled with Raxm's JavaScript assets, you must include @verbatim`@raxmScripts`@endverbatim on every page you wish to use Alpine. Even if you're not using Raxm on that page.

Though rarely required, you may disable Raxm's auto-injecting asset behavior by updating the `inject_assets` [configuration option](#publishing-config) in your application's `config/raxm.php` file:

```php
'inject_assets' => false,
```

If you'd rather force Raxm to inject it's assets on a single page or multiple pages, you can call the following global method from the current route or from a service provider.

```php
Axm\Raxm::forceAssetInjection();
```

## Configuring Raxm's update endpoint

Every update in a Raxm component sends a network request to the server at the following endpoint: `https://example.com/raxm/update`

This can be a problem for some applications that use localization or multi-tenancy.

In those cases, you can register your own endpoint however you like, and as long as you do it inside `Raxm::setUpdateRoute()`, Raxm will know to use this endpoint for all component updates:

```php
Raxm::setUpdateRoute(function ($handle) {
	return Route::post('/custom/raxm/update', $handle);
});
```

Now, instead of using `/raxm/update`, Raxm will send component updates to `/custom/raxm/update`.

Because Raxm allows you to register your own update route, you can declare any additional middleware you want Raxm to use directly inside `setUpdateRoute()`:

```php
Raxm::setUpdateRoute(function ($handle) {
	return Route::post('/custom/raxm/update', $handle)
        ->middleware([...]); // [tl! highlight]
});
```

## Customizing the asset URL

By default, Raxm will serve its JavaScript assets from the following URL: `https://example.com/raxm/raxm.js`. Additionally, Raxm will reference this asset from a script tag like so:

```html
<script src="/raxm/raxm.js" ...
```

If your application has global route prefixes due to localization or multi-tenancy, you can register your own endpoint that Raxm should use internally when fetching its JavaScript.

To use a custom JavaScript asset endpoint, you can register your own route inside `Raxm::setScriptRoute()`:

```php
Raxm::setScriptRoute(function ($handle) {
    return Route::get('/custom/raxm/raxm.js', $handle);
});
```

Now, Raxm will load its JavaScript like so:

```html
<script src="/custom/raxm/raxm.js" ...
```

## Manually bundling Raxm and Alpine

By default, Alpine and Raxm are loaded using the `<script src="raxm.js">` tag, which means you have no control over the order in which these libraries are loaded. Consequently, importing and registering Alpine plugins, as shown in the example below, will no longer function:

```js
// Warning: This snippet demonstrates what NOT to do...

import Alpine from "alpinejs";
import Clipboard from "@ryangjchandler/alpine-clipboard";

Alpine.plugin(Clipboard);
Alpine.start();
```

To address this issue, we need to inform Raxm that we want to use the ESM (ECMAScript module) version ourselves and prevent the injection of the `raxm.js` script tag. To achieve this, we must add the `@raxmScriptConfig` directive to our layout file (`resources/views/components/layouts/app.blade.php`):

```html
<html>
  <head>
    <!-- ... -->
    @raxmStyles @vite(['resources/js/app.js'])
  </head>
  <body>
    <?= $slot ?>

    @raxmScriptConfig
    <!-- [tl! highlight] -->
  </body>
</html>
```

When Raxm detects the `@raxmScriptConfig` directive, it will refrain from injecting the Raxm and Alpine scripts. If you are using the `@raxmScripts` directive to manually load Raxm, be sure to remove it. Make sure to add the `@raxmStyles` directive if it is not already present.

The final step is importing Alpine and Raxm in our `app.js` file, allowing us to register any custom resources, and ultimately starting Raxm and Alpine:

```js
import { Raxm, Alpine } from "../../vendor/raxm/raxm/dist/raxm.esm";
import Clipboard from "@ryangjchandler/alpine-clipboard";

Alpine.plugin(Clipboard);

Raxm.start();
```
