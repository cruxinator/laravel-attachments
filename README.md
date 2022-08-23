# :package_description

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cruxinator/laravel-attachments.svg?style=flat-square)](https://packagist.org/packages/cruxinator/laravel-attachments)
[![run-tests](https://github.com/cruxinator/laravel-strings/actions/workflows/run-tests.yml/badge.svg)](https://github.com/cruxinator/laravel-attachments/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/cruxinator/laravel-strings/actions/workflows/php-cs-fixer.yml/badge.svg)](https://cruxinator/laravel-attachments/laravel-strings/actions/workflows/php-cs-fixer.yml)
[![PHPStan](https://github.com/cruxinator/laravel-strings/actions/workflows/phpstan.yml/badge.svg)](https://github.com/cruxinator/laravel-attachments/actions/workflows/phpstan.yml)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

## Installation

You can install the package via composer:

```bash
composer require cruxinator/laravel-attachments
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-attachments-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-attachments-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-attachments-views"
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
