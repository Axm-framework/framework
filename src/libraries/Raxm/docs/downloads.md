File downloads in Raxm work much the same as in Raxm itself. Typically, you can use any Raxm download utility inside a Raxm component and it should work as expected.

However, behind the scenes, file downloads are handled differently than in a standard Raxm application. When using Raxm, the file's contents are Base64 encoded, sent to the frontend, and decoded back into binary to be downloaded directly from the client.

## Basic usage

Triggering a file download in Raxm is as simple as returning a standard Raxm download response.

Below is an example of a `ShowInvoice` component that contains a "Download" button to download the invoice PDF:

```php
<?php

namespace App\Raxm;

use App\Raxm;
use App\Models\Invoice;

class ShowInvoice extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function download()
    {
        return response()->download( // [tl! highlight:2]
            $this->invoice->file_path, 'invoice.pdf'
        );
    }

    public function render()
    {
        return view('raxm.show-invoice');
    }
}
```

```html
<div>
  <h1><?= $invoice->title ?></h1>

  <span><?= $invoice->date ?></span>
  <span><?= $invoice->amount ?></span>

  <button type="button" axm:click="download">Download</button>
  <!-- [tl! highlight] -->
</div>
```

Just like in a Raxm controller, you can also use the `Storage` facade to initiate downloads:

```php
public function download()
{
    return Storage::disk('invoices')->download('invoice.csv');
}
```

## Streaming downloads

Raxm can also stream downloads; however, they aren't truly streamed. The download isn't triggered until the file's contents are collected and delivered to the browser:

```php
public function download()
{
    return response()->streamDownload(function () {
        echo '...'; // Echo download contents directly...
    }, 'invoice.pdf');
}
```

## Testing file downloads

Raxm also provides a `->assertFileDownloaded()` method to easily test that a file was downloaded with a given name:

```php
use App\Models\Invoice;

/** @test */
public function can_download_invoice()
{
    $invoice = Invoice::factory();

    Raxm::test(ShowInvoice::class)
        ->call('download')
        ->assertFileDownloaded('invoice.pdf');
}
```
