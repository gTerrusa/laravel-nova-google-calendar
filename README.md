# Laravel Google Calendar package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gterrusa/laravel-nova-google-calendar.svg?style=flat-square)](https://packagist.org/packages/gterrusa/laravel-nova-google-calendar)
[![Total Downloads](https://img.shields.io/packagist/dt/gterrusa/laravel-nova-google-calendar.svg?style=flat-square)](https://packagist.org/packages/gterrusa/laravel-nova-google-calendar)

A Laravel Nova tool that provides an interface with Google Calendar.

## Installation

You can install the package via composer:

```bash
composer require gterrusa/laravel-nova-google-calendar
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="GTerrusa\LaravelNovaGoogleCalendar\ToolServiceProvider"
```

This is the contents of the published config file:

```php
return [
    'user_admin_boolean' => null
];

```

## Setup

1. Follow the setup steps [here](https://github.com/gTerrusa/laravel-google-calendar)
2. Include the tool in your NovaServiceProvider
```php
    ...
    use GTerrusa\LaravelNovaGoogleCalendar\LaravelNovaGoogleCalendar;
    
    ...

    public function tools()
    {
        return [
            ...,
            new LaravelNovaGoogleCalendar()
        ];
    }

```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [gTerrusa](https://github.com/gTerrusa)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
