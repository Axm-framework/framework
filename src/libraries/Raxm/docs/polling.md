Polling is a technique used in web applications to "poll" the server (send regular requests) for updates. It's a simple way to keep a page up-to-date without the need for a more sophisticated technology like [WebSockets](/docs/events#real-time-events-using-laravel-echo).

## Basic usage

Using polling inside Raxm is as simple as adding `axm:poll` to an element.

Below is an example of a `SubscriberCount` component that shows a user's subscriber count:

```php
<?php

namespace App\Raxm;

use Illuminate\Support\Facades\Auth;
use App\Raxm;

class SubscriberCount extends Component
{
    public function render()
    {
        return view('raxm.subscriber-count', [
            'count' => Auth::user()->subscribers->count(),
        ]);
    }
}
```

```html
<div axm:poll>
  Subscribers:
  <?= $count ?>
</div>
```

Normally, this component would show the subscriber count for the user and never update until the page was refreshed. However, because of `axm:poll` on the component's template, this component will now refresh itself every `2.5` seconds, keeping the subscriber count up-to-date.

You can also specify an action to fire on the polling interval by passing a value to `axm:poll`:

```html
<div axm:poll="refreshSubscribers">
  Subscribers:
  <?= $count ?>
</div>
```

Now, the `refreshSubscribers()` method on the component will be called every `2.5` seconds.

## Timing control

The primary drawback of polling is that it can be resource intensive. If you have a thousand visitors on a page that uses polling, one thousand network requests will be triggered every `2.5` seconds.

The best way to reduce requests in this scenario is simply to make the polling interval longer.

You can manually control how often the component will poll by appending the desired duration to `axm:poll` like so:

```html
<div axm:poll.15s>
  <!-- In seconds... -->

  <div axm:poll.15000ms><!-- In milliseconds... --></div>
</div>
```

## Background throttling

To further cut down on server requests, Raxm automatically throttles polling when a page is in the background. For example, if a user keeps a page open in a different browser tab, Raxm will reduce the number of polling requests by 95% until the user revisits the tab.

If you want to opt-out of this behavior and keep polling continuously, even when a tab is in the background, you can add the `.keep-alive` modifier to `axm:poll`:

```html
<div axm:poll.keep-alive></div>
```

## Viewport throttling

Another measure you can take to only poll when necessary, is to add the `.visible` modifier to `axm:poll`. The `.visible` modifier instructs Raxm to only poll the component when it is visible on the page:

```html
<div axm:poll.visible></div>
```

If a component using `axm:visible` is at the bottom of a long page, it won't start polling until the user scrolls it into the viewport. When the user scrolls away, it will stop polling again.
