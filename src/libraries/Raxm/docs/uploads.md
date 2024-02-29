Raxm offers powerful support for uploading files within your components.

First, add the `WithFileUploads` trait to your component. Once this trait has been added to your component, you can use `axm:model` on file inputs as if they were any other input type and Raxm will take care of the rest.

Here's an example of a simple component that handles uploading a photo:

```php
<?php

namespace App\Raxm;

use App\Raxm;
use Raxm\WithFileUploads;
use Raxm\Attributes\Rule;

class UploadPhoto extends Component
{
    use WithFileUploads;

    #[Rule('image|max:1024')] // 1MB Max
    public $photo;

    public function save()
    {
        $this->photo->store('photos');
    }
}
```

```html
<form axm:submit="save">
  <input type="file" axm:model="photo" />

  @error('photo') <span class="error"><?= $message ?></span> @enderror

  <button type="submit">Save photo</button>
</form>
```

> [!warning] The term "upload" is reserved
> The term "upload" is reserved by Raxm. You cannot use it as a method or property name.

From the developer's perspective, handling file inputs is no different than handling any other input type: Add `axm:model` to the `<input>` tag and everything else is taken care of for you.

However, more is happening under the hood to make file uploads work in Raxm. Here's a glimpse at what goes on when a user selects a file to upload:

1. When a new file is selected, Raxm's JavaScript makes an initial request to the component on the server to get a temporary "signed" upload URL.
2. Once the URL is received, JavaScript does the actual "upload" to the signed URL, storing the upload in a temporary directory designated by Raxm and returning the new temporary file's unique hash ID.
3. Once the file is uploaded and the unique hash ID is generated, Raxm's JavaScript makes a final request to the component on the server, telling it to "set" the desired public property to the new temporary file.
4. Now, the public property (in this case, `$photo`) is set to the temporary file upload and is ready to be stored or validated at any point.

## Storing uploaded files

The previous example demonstrates the most basic storage scenario: moving the temporarily uploaded file to the "photos" directory on the application's default filesystem disk.

However, you may want to customize the file name of the stored file or even specify a specific storage "disk" to keep the file on (such as S3).

Raxm honors the same APIs Raxm uses for storing uploaded files, so feel free to consult [Raxm's file upload documentation](https://laravel.com/docs/filesystem#file-uploads). However, below are a few common storage scenarios and examples:

```php
public function save()
{
    // Store the file in the "photos" directory of the default filesystem disk
    $this->photo->store('photos');

    // Store the file in the "photos" directory in a configured "s3" disk
    $this->photo->store('photos', 's3');

    // Store the file in the "photos" directory with the filename "avatar.png"
    $this->photo->storeAs('photos', 'avatar');

    // Store the file in the "photos" directory in a configured "s3" disk with the filename "avatar.png"
    $this->photo->storeAs('photos', 'avatar', 's3');

    // Store the file in the "photos" directory, with "public" visibility in a configured "s3" disk
    $this->photo->storePublicly('photos', 's3');

    // Store the file in the "photos" directory, with the name "avatar.png", with "public" visibility in a configured "s3" disk
    $this->photo->storePubliclyAs('photos', 'avatar', 's3');
}
```

## Handling multiple files

Raxm automatically handles multiple file uploads by detecting the `multiple` attribute on the `<input>` tag.

For example, below is a component with an array property named `$photos`. By adding `multiple` to the form's file input, Raxm will automatically append new files to this array:

```php
use App\Raxm;
use Raxm\WithFileUploads;
use Raxm\Attributes\Rule;

class UploadPhotos extends Component
{
    use WithFileUploads;

    #[Rule(['photos.*' => 'image|max:1024'])]
    public $photos = [];

    public function save()
    {
        foreach ($this->photos as $photo) {
            $photo->store('photos');
        }
    }
}
```

```html
<form axm:submit="save">
  <input type="file" axm:model="photos" multiple />

  @error('photos.*') <span class="error"><?= $message ?></span> @enderror

  <button type="submit">Save photo</button>
</form>
```

## File validation

Like we've discussed, validating file uploads with Raxm is the same as handling file uploads from a standard Raxm controller.

> [!warning] Ensure S3 is properly configured
> Many of the validation rules relating to files require access to the file. When [uploading directly to S3](#upload-to-s3), these validation rules will fail if the S3 file object is not publicly accessible.

For more information on file validation, consult [Raxm's file validation documentation](https://laravel.com/docs/validation#available-validation-rules).

## Temporary preview URLs

After a user chooses a file, you should typically show them a preview of that file before they submit the form and store the file.

Raxm makes this trivial by using the `->temporaryUrl()` method on uploaded files.

> [!info] Temporary URLs are restricted to images
> For security reasons, temporary upload URLs are only supported on files with image MIME types.

Let's explore an example of a file upload with an image preview:

```php
use App\Raxm;
use Raxm\WithFileUploads;
use Raxm\Attributes\Rule;

class UploadPhoto extends Component
{
    use WithFileUploads;

    #[Rule('image|max:1024')]
    public $photo;

    // ...
}
```

```html
<form axm:submit="save">
  if ($photo)
  <!-- [tl! highlight:2] -->
  <img src="<?= $photo->temporaryUrl() ?>" />
  endif

  <input type="file" axm:model="photo" />

  @error('photo') <span class="error"><?= $message ?></span> @enderror

  <button type="submit">Save photo</button>
</form>
```

As previously discussed, Raxm stores temporary files in a non-public directory; therefore, typically there's no simple way to expose a temporary, public URL to your users for image previewing.

However, Raxm solves this issue by providing a temporary, signed URL that pretends to be the uploaded image so your page can show an image preview to your users.

This URL is protected against showing files in directories above the temporary directory. And, because it's signed, users can't abuse this URL to preview other files on your system.

> [!tip] S3 temporary signed URLs
> If you've configured Raxm to use S3 for temporary file storage, calling `->temporaryUrl()` will generate a temporary, signed URL to S3 directly so that image previews aren't loaded from your Raxm application server.

## Testing file uploads

You can use Raxm's existing file upload testing helpers to test file uploads.

Below is a complete example of testing the `UploadPhoto` component with Raxm:

```php
<?php

namespace Tests\Feature\Raxm;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Raxm\UploadPhoto;
use Raxm\Raxm;
use Tests\TestCase;

class UploadPhotoTest extends TestCase
{
    /** @test */
    public function can_upload_photo()
    {
        Storage::fake('avatars');

        $file = UploadedFile::fake()->image('avatar.png');

        Raxm::test(UploadPhoto::class)
            ->set('photo', $file)
            ->call('upload', 'uploaded-avatar.png');

        Storage::disk('avatars')->assertExists('uploaded-avatar.png');
    }
}
```

Below is an example of the `UploadPhoto` component required to make the previous test pass:

```php
use App\Raxm;
use Raxm\WithFileUploads;

class UploadPhoto extends Component
{
    use WithFileUploads;

    public $photo;

    public function upload($name)
    {
        $this->photo->storeAs('/', $name, disk: 'avatars');
    }

    // ...
}
```

For more information on testing file uploads, please consult [Raxm's file upload testing documentation](https://laravel.com/docs/http-tests#testing-file-uploads).

## Uploading directly to Amazon S3

As previously discussed, Raxm stores all file uploads in a temporary directory until the developer permanently stores the file.

By default, Raxm uses the default filesystem disk configuration (usually `local`) and stores the files within a `raxm-tmp/` directory.

Consequently, file uploads are always utilizing your application server, even if you choose to store the uploaded files in an S3 bucket later.

If you wish to bypass your application server and instead store Raxm's temporary uploads in an S3 bucket, you can configure that behavior in your application's `config/raxm.php` configuration file. First, set `raxm.temporary_file_upload.disk` to `s3` (or another custom disk that uses the `s3` driver):

```php
return [
    // ...
    'temporary_file_upload' => [
        'disk' => 's3',
        // ...
    ],
];
```

Now, when a user uploads a file, the file will never actually be stored on your server. Instead, it will be uploaded directly to your S3 bucket within the `raxm-tmp/` sub-directory.

> [!info] Publishing Raxm's configuration file
> Before customizing the file upload disk, you must first publish Raxm's configuration file to your application's `/config` directory by running the following command:
>
> ```shell
> php axm raxm:publish --config
> ```

### Configuring automatic file cleanup

Raxm's temporary upload directory will fill up with files quickly; therefore, it's essential to configure S3 to clean up files older than 24 hours.

To configure this behavior, run the following Artisan command from the environment that is utilizing an S3 bucket for file uploads:

```shell
php axm raxm:configure-s3-upload-cleanup
```

Now, any temporary files older than 24 hours will be cleaned up by S3 automatically.

> [!info]
> If you are not using S3 for file storage, Raxm will handle file cleanup automatically and there is no need to run the command above.

## Loading indicators

Although `axm:model` for file uploads works differently than other `axm:model` input types under the hood, the interface for showing loading indicators remains the same.

You can display a loading indicator scoped to the file upload like so:

```html
<input type="file" axm:model="photo" />

<div axm:loading axm:target="photo">Uploading...</div>
```

Now, while the file is uploading, the "Uploading..." message will be shown and then hidden when the upload is finished.

For more information on loading states, check out our comprehensive [loading state documentation](/docs/loading).

## Progress indicators

Every Raxm file upload operation dispatches JavaScript events on the corresponding `<input>` element, allowing custom JavaScript to intercept the events:

| Event                  | Description                                                                 |
| ---------------------- | --------------------------------------------------------------------------- |
| `raxm-upload-start`    | Dispatched when the upload starts                                           |
| `raxm-upload-finish`   | Dispatched if the upload is successfully finished                           |
| `raxm-upload-error`    | Dispatched if the upload fails                                              |
| `raxm-upload-progress` | An event containing the upload progress percentage as the upload progresses |

Below is an example of wrapping a Raxm file upload in an Alpine component to display an upload progress bar:

```html
<form axm:submit="save">
  <div
    x-data="{ uploading: false, progress: 0 }"
    x-on:raxm-upload-start="uploading = true"
    x-on:raxm-upload-finish="uploading = false"
    x-on:raxm-upload-error="uploading = false"
    x-on:raxm-upload-progress="progress = $event.detail.progress"
  >
    <!-- File Input -->
    <input type="file" axm:model="photo" />

    <!-- Progress Bar -->
    <div x-show="uploading">
      <progress max="100" x-bind:value="progress"></progress>
    </div>
  </div>

  <!-- ... -->
</form>
```

## JavaScript upload API

Integrating with third-party file-uploading libraries often requires more control than a simple `<input type="file">` tag.

For these cases, Raxm exposes dedicated JavaScript functions.

These functions exist on a JavaScript component object, which can be accessed using Raxm's convenient `@this` Blade directive from within your Raxm component's template:

```html
<script>
  let file = document.querySelector('input[type="file"]').files[0]

  // Upload a file
  @this.upload('photo', file, (uploadedFilename) => {
      // Success callback...
  }, () => {
      // Error callback...
  }, (event) => {
      // Progress callback...
      // event.detail.progress contains a number between 1 and 100 as the upload progresses
  })

  // Upload multiple files
  @this.uploadMultiple('photos', [file], successCallback, errorCallback, progressCallback)

  // Remove single file from multiple uploaded files
  @this.removeUpload('photos', uploadedFilename, successCallback)
</script>
```

## Configuration

Because Raxm stores all file uploads temporarily before the developer can validate or store them, it assumes some default handling behavior for all file uploads.

### Global validation

By default, Raxm will validate all temporary file uploads with the following rules: `file|max:12288` (Must be a file less than 12MB).

If you wish to customize these rules, you can do so inside your application's `config/raxm.php` file:

```php
'temporary_file_upload' => [
    // ...
    'rules' => 'file|mimes:png,jpg,pdf|max:102400', // (100MB max, and only accept PNGs, JPEGs, and PDFs)
],
```

### Global middleware

The temporary file upload endpoint is assigned a throttling middleware by default. You can customize exactly what middleware this endpoint uses via the following configuration option:

```php
'temporary_file_upload' => [
    // ...
    'middleware' => 'throttle:5,1', // Only allow 5 uploads per user per minute
],
```

### Temporary upload directory

Temporary files are uploaded to the specified disk's `raxm-tmp/` directory. You can customize this directory via the following configuration option:

```php
'temporary_file_upload' => [
    // ...
    'directory' => 'tmp',
],
```
