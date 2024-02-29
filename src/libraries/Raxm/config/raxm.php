<?php

return [

    'raxm' => [

        /**
         --------------------------------------------------------------------------
          CLASS NAMESPACE
         --------------------------------------------------------------------------
         *
         * This value sets the root namespace for raxm component classes in
         * your application. This value affects component auto-discovery and
         * any raxm file helper commands, like `axm make:raxm`.
         *
         * After changing this item, run: `php axm raxm:discover`.
         * @var string 
         */
        'class_namespace' => 'App\\Raxm\\',

        /**
         --------------------------------------------------------------------------
          VIEW PATH
         --------------------------------------------------------------------------
         *
         * This value sets the path for raxm component views. This affects
         * file manipulation helper commands like `axm make:raxm`.
         * @var string 
         */
        'view_path' => config('paths.viewsPath') . DIRECTORY_SEPARATOR . 'raxm'
            . DIRECTORY_SEPARATOR,

        /**
         --------------------------------------------------------------------------
          COMPONENT PATH
         --------------------------------------------------------------------------
         *
         * This value sets the path for raxm component views. This affects
         * file manipulation helper commands like `axm make:raxm`.
         * @var string 
         */
        'component_path' => ROOT_PATH . DIRECTORY_SEPARATOR . 'Raxm',

        /**
         --------------------------------------------------------------------------
          LAYOUT NAME
         --------------------------------------------------------------------------
         *
         * The default layout view that will be used when rendering a component via
         * Route::get('/some-endpoint', SomeComponent::class);. In this case the
         * the view returned by SomeComponent will be wrapped in "layouts.app"
         * @var string 
         */
        'layout' => 'main',

        /**
         --------------------------------------------------------------------------
          LAYOUT PATH
         --------------------------------------------------------------------------
         *
         * The default layout view that will be used when rendering a component via
         * Route::get('/some-endpoint', SomeComponent::class);. In this case the
         * the view returned by SomeComponent will be wrapped in "layouts.app"
         * @var string 
         */
        'layoutPath' => ROOT_PATH . DIRECTORY_SEPARATOR . 'resources'
            . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts',

        /**
         --------------------------------------------------------------------------
          RAXM ASSETS URL
         --------------------------------------------------------------------------
         *
         * This value sets the path to raxm JavaScript assets, for cases where
         * your app's domain root is not the correct path. By default, raxm
         * will load its JavaScript assets from the app's "relative root".
         * @var string 
         */
        'asset_url' => '/raxm/raxm_js',

        /**
         --------------------------------------------------------------------------
          RAXM APP URL
         --------------------------------------------------------------------------
         *
         * This value should be used if raxm assets are served from CDN.
         * raxm will communicate with an app through this url.
         * @var string 
         */
        'app_url' => rtrim(generateUrl(), '/'),

        /**
         --------------------------------------------------------------------------
          RAXM TEMPORARY FILE UPLOADS ENDPOINT CONFIGURATION
         --------------------------------------------------------------------------
         *
         * raxm handles file uploads by storing uploads in a temporary directory
         * before the file is validated and stored permanently. All file uploads
         * are directed to a global endpoint for temporary storage. The config
         * items below are used for customizing the way the endpoint works.
         * @var array 
         */
        'temporary_file_upload' => [
            'disk' => null,        // Example: 'local', 's3'              | Default: 'default'
            'rules' => null,       // Example: ['file', 'mimes:png,jpg']  | Default: ['required', 'file', 'max:12288'] (12MB)
            'directory' => null,   // Example: 'tmp'                      | Default: 'Raxm-tmp'
            'middleware' => null,  // Example: 'throttle:5,1'             | Default: 'throttle:60,1'
            'preview_mimes' => [   // Supported file types for temporary pre-signed file URLs...
                'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
                'mov', 'avi', 'wmv', 'mp3', 'm4a',
                'jpg', 'jpeg', 'mpga', 'webp', 'wma',
            ],
            'max_upload_time' => 5, // Max duration (in minutes) before an upload is invalidated...
        ],

        /**
         --------------------------------------------------------------------------
          BACK BUTTON CACHE
         --------------------------------------------------------------------------
         *
         * This value determines whether the back button cache will be used on pages
         * that contain raxm. By disabling back button cache, it ensures that
         * the back button shows the correct state of components, instead of
         * potentially stale, cached data.
         *
         * Setting it to "false" (default) will disable back button cache.
         * @var bool 
         */
        'back_button_cache' => false,

        /**
         --------------------------------------------------------------------------
          RENDER ON REDIRECT
         --------------------------------------------------------------------------
         *
         * This value determines whether raxm will render before it's redirected
         * or not. Setting it to "false" (default) will mean the render method is
         * skipped when redirecting. And "true" will mean the render method is
         * run before redirecting. Browsers bfcache can store a potentially
         * stale view if render is skipped on redirect.
         * @var bool 
         */
        'render_on_redirect' => false,

        /**
         --------------------------------------------------------------------------
          NAVIGATE (SPA MODE)
         --------------------------------------------------------------------------
         *
         * By adding `axm:navigate` to links in your Raxm application, Raxm
         * will prevent the default link handling and instead request those pages
         * via AJAX, creating an SPA-like effect. Configure this behavior here.
         * @var array 
         */
        'navigate' => [
            'show_progress_bar' => true,
            'progress_bar_color' => '#1695DF',
        ],
    ]
];
