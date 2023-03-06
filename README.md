# Laravel File Uploader

This is a PHP package that helps to sanitize your input when performing search queries to DB.
It cleans the input from all malicious characters.

## Installation

You can install the package via composer:

```bash
composer require erlandmuchasaj/laravel-file-uploader
```

## Usage

Sanitize library offers a sanitize method that takes a parameter a dirty string parameter and return a cleaned string.  

```php
use ErlandMuchasaj\LaravelFileUploader\FileUploader;

$dirtyString = 'Hello - World';

$sanitizedString = FileUploader::upload($file);

// do something with the sanitized text
// you can: $words = explode(' ', $sanitizedString);
// and search the DB per each word.

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
