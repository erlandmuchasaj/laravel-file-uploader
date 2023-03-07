# Laravel File Uploader

Laravel File Uploader offers an easy way to upload files to different disks.
The main purpose of the package is to remove the repeated and cumbersome code and simplify it into some simple methods.

## Installation

You can install the package via composer:

```bash
composer require erlandmuchasaj/laravel-file-uploader
```

## Usage

This package has an very easy and straight-forward usage. 
Just import the package and pass the file as parameter, and it will handle the rest.

```php
use ErlandMuchasaj\LaravelFileUploader\FileUploader;

Route::post('/files', function (\Illuminate\Http\Request $request) {

    $max_size = (int) ini_get('upload_max_filesize') * 1000;
    
    // FileUploader::images() get all image extensions ex: jpg, png, jpeg, gif, etc.
    // FileUploader::documents() get all documents extensions ex: 'csv', 'html', 'pdf', 'doc', 'docx', 'ppt' etc.
    $extensions = implode(',', FileUploader::images());

    $request->validate([
        'file' => [
            'required',
            'file',
            'image',
            'mimes:' . $extensions,
            'max:'.$max_size,
        ]
    ]);

    $file = $request->file('file');

    $response = FileUploader::store($file);
    // $response = FileUploader::store($file, $options);
    // available options (as key=>value pare) are:
    // `disk`, 'user_id`, `path`, `visibility`
    
    // do something with the $response
    // you can save it into your model etc.
    
    return redirect()
            ->back()
            ->with('success', __('File has been uploaded.'))
            ->with('file', $response);
})->name('files.store');

/**
 *  $response = [
 *      "type" => "image"
 *      "extension" => "png"
 *      "_extension" => "png"
 *      "name" => "blog3"
 *      "original_name" => "blog3.png"
 *      "size" => 549247
 *      "mime_type" => "image/png"
 *      "dimensions" => "670x841"
 *      "path" => "uploads/1/image/blog3_1678118034.png" // <==
 *      "url" => "/storage/uploads/1/image/blog3_1678118034.png"
 *      "user_id" => 1
 *      "disk" => "local"
 *      "visibility" => "public"
 *      "uuid" => "dd5889c0-5057-49ef-a6ef-e3da961a47d1"
 *  ]
*/

```

If you need to modify the config files, you should publish the migration and the config/permission.php config file 
with:
```bash
php artisan vendor:publish --provider="ErlandMuchasaj\LaravelFileUploader\FileUploaderServiceProvider"
```

Some other helper methods:

```php
    $path = 'uploads/1/image/blog3_1678118034.png'; // the path of the image where is stored.
    $response = FileUploader::get($path); // get file as StreamedResponse
    $response = FileUploader::getFile($path); // get file as content.
    $response = FileUploader::url($path); // full path url - /storage/uploads/1/image/blog3_1678118034.png
    $response = FileUploader::path($path); // C:\wamp\www\laravel-app\storage\app\uploads/1/image/blog3_1678118034.png
    $response = FileUploader::meta($path); // metadata about the file.
    /**
    * [
    *     "path" => "C:\wamp\www\laravel-app\storage\app\uploads/1/image/blog3_1678118034.png"
    *     "url" => "/storage/uploads/1/image/blog3_1678118034.png"
    *     "visibility" => "public"
    *     "mimeType" => "image/png"
    *     "size" => "536.37 KB"
    *     "last_modified" => "1 hour ago"
    *     "name" => "blog3_1678118034.png"
    *     "pathinfo" => [
    *         "dirname" => "uploads/1/image"
    *         "basename" => "blog3_1678118034.png"
    *         "extension" => "png"
    *         "filename" => "blog3_1678118034"
    *     ]
    * ]
    */
        
    $response = FileUploader::download($path, 'something_nice'); // download the file as StreamedResponse
    $response = FileUploader::getVisibility($path); // file visibility when applicable private/public
    $response = FileUploader::setVisibility($path, 'private'); // change file visibility
    $response = FileUploader::remove($path); // delete a file
```

Also, some other size converting helper functions are available for example:

```php
    $size = 549247;
    FileUploader::formatBytes($size); // "536.37 KB"
    
    FileUploader::convertBytesToSpecified($size, 'KB'); // 536.37KB
    FileUploader::convertBytesToSpecified($size, 'MB'); // 0.52MB
```

---

## Support me

I invest a lot of time and resources into creating [best in class open source packages](https://github.com/erlandmuchasaj?tab=repositories).

If you found this package helpful you can show support by clicking on the following button below and donating some amount to help me work on these projects frequently.

<a href="https://www.buymeacoffee.com/erland" target="_blank">
    <img src="https://www.buymeacoffee.com/assets/img/guidelines/download-assets-2.svg" style="height: 45px; border-radius: 12px" alt="buy me a coffee"/>
</a>

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please see [SECURITY](SECURITY.md) for details.

## Credits

- [Erland Muchasaj](https://github.com/erlandmuchasaj)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
