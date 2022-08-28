# :package_description

[![Latest Version on Packagist](https://img.shields.io/packagist/v/:vendor_slug/:package_slug.svg?style=flat-square)](https://packagist.org/packages/:vendor_slug/:package_slug)
[![run-tests](https://github.com/cruxinator/laravel-strings/actions/workflows/run-tests.yml/badge.svg)](https://github.com/:vendor_slug/:package_slug/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/cruxinator/laravel-strings/actions/workflows/php-cs-fixer.yml/badge.svg)](https://:vendor_slug/:package_slug/laravel-strings/actions/workflows/php-cs-fixer.yml)
[![PHPStan](https://github.com/cruxinator/laravel-strings/actions/workflows/phpstan.yml/badge.svg)](https://github.com/:vendor_slug/:package_slug/actions/workflows/phpstan.yml)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

## Installation

You can install the package via composer:

```bash
composer require :vendor_slug/:package_slug
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag=":package_slug-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag=":package_slug-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag=":package_slug-views"
```

## Usage

```php
$variable = new VendorName\Skeleton();
echo $variable->echoPhrase('Hello, VendorName!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [:author_name](https://github.com/:author_username)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
