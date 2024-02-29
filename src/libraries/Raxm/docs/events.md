Raxm offers a robust event system that you can use to communicate between different components on the page. Because it uses browser events under the hood, you can also use Raxm's event system to communicate with Alpine components or even plain, vanilla JavaScript.

To trigger an event, you may use the `dispatch()` method from anywhere inside your component and listen for that event from any other component on the page.

## Dispatching events

To dispatch an event from a Raxm component, you can call the `dispatch()` method, passing it the event name and any additional data you want to send along with the event.

Below is an example of dispatching a `post-created` event from a `CreatePost` component:

```php
use App\Raxm;

class CreatePost extends Component
{
    public function save()
    {
		// ...

		$this->dispatch('post-created'); // [tl! highlight]
    }
}
```

In this example, when the `dispatch()` method is called, the `post-created` event will be dispatched, and every other component on the page that is listening for this event will be notified.

You can pass additional data with the event by passing the data as the second parameter to the `dispatch()` method:

```php
$this->dispatch('post-created', title: $post->title);
```

## Listening for events

To listen for an event in a Raxm component, add the `#[On]` attribute above the method you want to be called when a given event is dispatched:

```php
use App\Raxm;
use Raxm\Attributes\On; // [tl! highlight]

class Dashboard extends Component
{
	#[On('post-created')] // [tl! highlight]
    public function updatePostList($title)
    {
		// ...
    }
}
```

Now, when the `post-created` event is dispatched from `CreatePost`, a network request will be triggered and the `updatePostList()` action will be invoked.

As you can see, additional data sent with the event will be provided to the action as its first argument.

### Listening for dynamic event names

Occasionally, you may want to dynamically generate event listener names at run-time using data from your component.

For example, if you wanted to scope an event listener to a specific Eloquent model, you could append the model's ID to the event name like so:

```php
use App\Raxm;
use App\Models\Post;
use Raxm\Attributes\On; // [tl! highlight]

class ShowPost extends Component
{
    public Post $post;

	#[On('post-updated.{post.id}')] // [tl! highlight]
    public function refreshPost()
    {
		// ...
    }
}
```

If the above `$post` model had an ID of `3`, the `refreshPost()` method would only be triggered by an event named: `post-updated.3`.

## Using events within inline scripts

You can dispatch and listen to events from inline scripts within your component's template.

### Listening for Raxm events in script tags

For example, we may easily listen for the `post-created` event using:

```html
<script>
    document.addEventListener('raxm:initialized', () => {
        @this.on('post-created', (event) => {
            //
        });
    });
</script>
```

The above snippet would listen for the `post-created` from the component its registered within.

### Dispatching Raxm events from script tags

Any event dispatched from an inline script is capable of being intercepted by any Raxm component on the page.

For example:

```html
<script>
    document.addEventListener('raxm:initialized', () => {
        @this.on('post-created', (event) => {
            @this.dispatch('refresh-posts'); // [tl! highlight]
        });
    });
</script>
```

The above snippet would dispatch a "refresh-posts" event after a "post-created" event was triggered from this component.

Like Raxm's `dispatch()` method, you can pass additional data along with the event by passing the data as the second parameter to the method:

```js
@this.dispatch('notify', { message: 'New post added.' });
```

To dispatch the event only to the component where the script resides and not other components on the page, you can use `dispatchSelf()`:

```js
@this.dispatchSelf('refresh-posts');
```

## Events in Alpine

Because Raxm events are plain browser events under the hood, you can use Alpine to listen for them or even dispatch them.

### Listening for Raxm events in Alpine

For example, we may easily listen for the `post-created` event using Alpine:

```html
<div x-on:post-created="..."></div>
```

The above snippet would listen for the `post-created` event from any Raxm components that are children of the HTML element that the `x-on` directive is assigned to.

To listen for the event from any Raxm component on the page, you can add `.window` to the listener:

```html
<div x-on:post-created.window="..."></div>
```

If you want to access additional data that was sent with the event, you can do so using `$event.detail`:

```html
<div x-on:post-created="notify('New post: ' + $event.detail.title)"></div>
```

The Alpine documentation provides further information on [listening for events](https://alpinejs.dev/directives/on).

### Dispatching Raxm events from Alpine

Any event dispatched from Alpine is capable of being intercepted by a Raxm component.

For example, we may easily dispatch the `post-created` event from Alpine:

```html
<button @click="$dispatch('post-created')">...</button>
```

Like Raxm's `dispatch()` method, you can pass additional data along with the event by passing the data as the second parameter to the method:

```html
<button @click="$dispatch('post-created', { title: 'Post Title' })">...</button>
```

To learn more about dispatching events using Alpine, consult the [Alpine documentation](https://alpinejs.dev/magics/dispatch).

> [!tip] You might not need events
> If you are using events to call behavior on a parent from a child, you can instead call the action directly from the child using `$parent` in your Blade template. For example:
>
> ```html
> <button axm:click="$parent.showCreatePostForm()">Create Post</button>
> ```
>
> [Learn more about $parent](/docs/nesting#directly-accessing-the-parent-from-the-child).

## Dispatching directly to another component

If you want to use events for communicating directly between two components on the page, you can use the `dispatch()->to()` modifier.

Below is an example of the `CreatePost` component dispatching the `post-created` event directly to the `Dashboard` component, skipping any other components listening for that specific event:

```php
use App\Raxm;

class CreatePost extends Component
{
    public function save()
    {
		// ...

		$this->dispatch('post-created')->to(Dashboard::class);
    }
}
```

## Dispatching a component event to itself

Using the `dispatch()->self()` modifier, you can restrict an event to only being intercepted by the component it was triggered from:

```php
use App\Raxm;

class CreatePost extends Component
{
    public function save()
    {
		// ...

		$this->dispatch('post-created')->self();
    }
}
```

## Dispatching events from Blade templates

You can dispatch events directly from your Blade templates using the `$dispatch` JavaScript function. This is useful when you want to trigger an event from a user interaction, such as a button click:

```html
<button axm:click="$dispatch('show-post-modal', { id: <?= $post->id ?> })">
  EditPost
</button>
```

In this example, when the button is clicked, the `show-post-modal` event will be dispatched with the specified data.

If you want to dispatch an event directly to another component you can use the `$dispatchTo()` JavaScript function:

```html
<button
  axm:click="$dispatchTo('posts', 'show-post-modal', { id: <?= $post->id ?> })"
>
  EditPost
</button>
```

In this example, when the button is clicked, the `show-post-modal` event will be dispatched directly to the `Posts` component.

## Testing dispatched events

To test events dispatched by your component, use the `assertDispatched()` method in your Raxm test. This method checks that a specific event has been dispatched during the component's lifecycle:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Raxm\CreatePost;
use Raxm\Raxm;

class CreatePostTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_dispatches_post_created_event()
    {
        Raxm::test(CreatePost::class)
            ->call('save')
            ->assertDispatched('post-created');
    }
}
```

In this example, the test ensures that the `post-created` event is dispatched with the specified data when the `save()` method is called on the `CreatePost` component.

### Testing Event Listeners

To test event listeners, you can dispatch events from the test environment and assert that the expected actions are performed in response to the event:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Raxm\Dashboard;
use Raxm\Raxm;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_updates_post_count_when_a_post_is_created()
    {
        Raxm::test(Dashboard::class)
            ->assertSee('Posts created: 0')
            ->dispatch('post-created')
            ->assertSee('Posts created: 1');
    }
}
```

In this example, the test dispatches the `post-created` event, then checks that the `Dashboard` component properly handles the event and displays the updated count.

## Real-time events using Raxm Echo

Raxm pairs nicely with [Raxm Echo](https://laravel.com/docs/broadcasting#client-side-installation) to provide real-time functionality on your web-pages using WebSockets.

> [!warning] Installing Raxm Echo is a prerequisite
> This feature assumes you have installed Raxm Echo and the `window.Echo` object is globally available in your application. For more information on installing echo, check out the [Raxm Echo documentation](https://laravel.com/docs/broadcasting#client-side-installation).

### Listening for Echo events

Imagine you have an event in your Raxm application named `OrderShipped`:

```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderShipped implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;

    public function broadcastOn()
    {
        return new Channel('orders');
    }
}
```

You might dispatch this event from another part of your application like so:

```php
use App\Events\OrderShipped;

OrderShipped::dispatch();
```

If you were to listen for this event in JavaScript using only Raxm Echo, it would look something like this:

```js
Echo.channel("orders").listen("OrderShipped", (e) => {
  console.log(e.order);
});
```

Assuming you have Raxm Echo installed and configured, you can listen for this event from inside a Raxm component.

Below is an example of an `OrderTracker` component that is listening for the `OrderShipped` event in order to show users a visual indication of a new order:

```php
<?php

namespace App\Raxm;

use Raxm\Attributes\On; // [tl! highlight]
use App\Raxm;

class OrderTracker extends Component
{
    public $showNewOrderNotification = false;

    #[On('echo:orders,OrderShipped')]
    public function notifyNewOrder()
    {
        $this->showNewOrderNotification = true;
    }

    // ...
}
```

If you have Echo channels with variables embedded in them (such as an Order ID), you can define listeners via the `getListeners()` method instead of the `#[On]` attribute:

```php
<?php

namespace App\Raxm;

use Raxm\Attributes\On; // [tl! highlight]
use App\Raxm;
use App\Models\Order;

class OrderTracker extends Component
{
    public Order $order;

    public $showOrderShippedNotification = false;

    public function getListeners()
    {
        return [
            "echo:orders.{$this->order->id},OrderShipped" => 'notifyShipped',
        ];
    }

    public function notifyShipped()
    {
        $this->showOrderShippedNotification = true;
    }

    // ...
}
```

Or, if you prefer, you can use the dynamic event name syntax:

```php
#[On('echo:orders.{order.id},OrderShipped')]
public function notifyNewOrder()
{
    $this->showNewOrderNotification = true;
}
```

If you need to access the event payload, you can do so via the passed in `$event` parameter:

```php
#[On('echo:orders.{order.id},OrderShipped')]
public function notifyNewOrder($event)
{
    $order = Order::find($event['orderId']);

    //
}
```

### Private & presence channels

You may also listen to events broadcast to private and presence channels:

> [!info]
> Before proceeding, ensure you have defined <a href="https://laravel.com/docs/master/broadcasting#defining-authorization-callbacks">Authentication Callbacks</a> for your broadcast channels.

```php
<?php

namespace App\Raxm;

use App\Raxm;

class OrderTracker extends Component
{
    public $showNewOrderNotification = false;

    public function getListeners()
    {
        return [
            // Public Channel
            "echo:orders,OrderShipped" => 'notifyNewOrder',

            // Private Channel
            "echo-private:orders,OrderShipped" => 'notifyNewOrder',

            // Presence Channel
            "echo-presence:orders,OrderShipped" => 'notifyNewOrder',
            "echo-presence:orders,here" => 'notifyNewOrder',
            "echo-presence:orders,joining" => 'notifyNewOrder',
            "echo-presence:orders,leaving" => 'notifyNewOrder',
        ];
    }

    public function notifyNewOrder()
    {
        $this->showNewOrderNotification = true;
    }
}
```
