Raxm properties are able to be modified freely on both the frontend and backend using utilities like `axm:model`. If you want to prevent a property — like a model ID — from being modified on the frontend, you can use Raxm's `#[Locked]` attribute.

## Basic usage

Below is a `ShowPost` component that stores a `Post` model's ID as a public property named `$id`. To keep this property from being modified by a curious or malicious user, you can add the `#[Locked]` attribute to the property:

```php
use Raxm\Attributes\Locked;
use App\Raxm;

class ShowPost extends Component
{
	#[Locked] // [tl! highlight]
    public $id;

    public function mount($postId)
    {
        $this->id = $postId;
    }

	// ...
}
```

By adding the `#[Locked]` attribute, you are ensured that the `$id` property will never be tampered with.

> [!tip] Model properties are secure by default
> If you store an Eloquent model in a public property instead of just the model's ID, Raxm will ensure the ID isn't tampered with, without you needing to explicitly add the `#[Locked]` attribute to the property. For most cases, this is a better approach than using `#[Locked]`:
>
> ```php
> class ShowPost extends Component
> {
>    public Post $post; // [tl! highlight]
>
>    public function mount($postId)
>    {
>        $this->post = Post::find($postId);
>    }
>
> 	// ...
> }
> ```

### Why not use protected properties?

You might ask yourself: why not just use protected properties for sensitive data?

Remember, Raxm only persists public properties between network requests. For static, hard-coded data, protected properties are suitable. However, for data that is stored at runtime, you must use a public property to ensure that the data is persisted properly.

### Can't Raxm do this automatically?

In a perfect world, Raxm would lock properties by default, and only allow modifications when `axm:model` is used on that property.

Unfortunately, that would require Raxm to parse all of your Blade templates to understand if a property is modified by `axm:model` or a similar API.

Not only would that add technical and performance overhead, it would be impossible to detect if a property is mutated by something like Alpine or any other custom JavaScript.

Therefore, Raxm will continue to make public properties freely mutable by default and give developers the tools to lock them as needed.
