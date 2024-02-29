# Axm Socialite

<!-- markdownlint-disable no-inline-html -->
<p align="center">
<a href="https://packagist.org/packages/axm/Socialite">
<img src="https://poser.pugx.org/axm/Socialite/v/stable" alt="Latest Stable Version"/></a>
<a href="https://packagist.org/packages/axm/Socialite">
<img src="https://poser.pugx.org/axm/Socialite/downloads" alt="Total Downloads"/></a>
<a href="https://packagist.org/packages/axm/Socialite">
<img src="https://poser.pugx.org/axm/Socialite/license" alt="License"/></a>
</p>
<br />
<br />

## 📦 Installation

To install the component, add the following to your `composer.json` file:

```json
{
    "require": {
        "axm/socialite": "^1.0"
    }
}
```

You can also use [Composer](https://getcomposer.org/) to install Axm in your project quickly.

```bash
composer require axm/Socialite
```

Then, run `composer update` to install the package.


## Configuration

Once the package is installed, you need to configure it. Open the `.env` file and add the following lines:

```php
GOOGLE_CLIENT_ID="YOUR_GOOGLE_CLIENT_ID"
GOOGLE_CLIENT_SECRET="YOUR_GOOGLE_CLIENT_SECRET"
GOOGLE_REDIRECT="${APP_URL}/YOUR_GOOGLE_REDIRECT_URI"

FACEBOOK_CLIENT_ID="YOUR_FACEBOOK_CLIENT_ID"
FACEBOOK_CLIENT_SECRET="YOUR_FACEBOOK_CLIENT_SECRET"
FACEBOOK_REDIRECT="${APP_URL}/YOUR_FACEBOOK_REDIRECT_URI"

```

Be sure to replace the placeholders with your actual Google and Facebook client IDs, client secrets, and redirect URIs.

## Usage

To use the component, simply add the following line to your route file:

```php
Route::get('/auth-redirect/{provider:\w+}', [App\Raxm\AuthComponent::class, 'handlerAuthRedirect']);
Route::get('/auth-google-callback', [App\Raxm\AuthComponent::class, 'handlerRediretGoogleAuth']);

```

This route will redirect the user to the authentication page of the specified provider.

Once the user has authenticated, they will be redirected back to your application. The `handleSocialAuthRedirect` method will then be called. This method will check if the user is already logged in. If they are, they will be redirected to the home page. If they are not, they will be registered as a new user and then redirected to the home page.

## Example

The following example shows how to use the component to authenticate users with Google and FaceBook,
usage in your controller:

```php
<?php

namespace App\Raxm;

use App\Models\User;
use Raxm\Component;
use Views\View;
use Socialite\Socialite;


class AuthComponent extends Component
{
    /**
     * @var array Associative array containing different types of authentication
     */
    protected $typeOfAuth = [
        'google'   => 'google',
        'facebook' => 'facebook'
    ];

    /**
     * Redirect to the corresponding social media authentication page
     * @return RedirectResponse
     */
    public function handlerAuthRedirect()
    {
        $provider = $this->getProvider();
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle social media authentication and redirect
     *
     * @param string $driver
     * @return void
     */
    protected function handleSocialAuthRedirect($user)
    {
        if (app()->login(['email', $user->email])) {
            redirect('/home');
        }
    }

    /**
     * Handle redirect after Google authentication.
     * @return void
     */
    public function handlerRediretGoogleAuth()
    {
        $user = Socialite::driver('google')->user();
        $this->handleSocialAuthRedirect($user);

        // Register a new user and redirect to home page 
        $this->registerNewUserAndRedirect($user);
    }

    /**
     * Handle redirect after Facebook authentication.
     * @return void
     */
    public function handlerRediretFacebookAuth()
    {
        $user = Socialite::driver('facebook')->user();
        $this->handleSocialAuthRedirect($user);

        // Register a new user and redirect to home page 
        $this->registerNewUserAndRedirect($user);
    }

    /**
     * Register new user and redirect
     *
     * @param object $user
     * @return void
     */
    protected function registerNewUserAndRedirect(object $user)
    {
        $newUser = new User;
        $newUser->name = $user->name;
        $newUser->email = $user->email;
        $newUser->save();

        app()->login($newUser);
        redirect('/home');
    }

    /**
     * This function retrieves the authentication provider from the route parameters
     * @return string
     */
    protected function getProvider(): string
    {
        $provider = app()->request->getRouteParam('provider');
        if (!array_key_exists($provider, $this->typeOfAuth)) {
            throw new \Exception('Authentication provider unknown');
        }

        return $this->typeOfAuth[$provider];
    }

}

```
