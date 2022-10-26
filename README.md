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
    /**
     * The accessor name of the attribute on your User model
     * to use to check if a User is an Admin.
     * Set to null to give all User's Admin privileges.
     */
    'user_admin_boolean' => null,

    /**
     * Should attendees be saved to Laravel Database?
     */
    'save_attendees_to_db' => false,

    /**
     * If saving attendees to Laravel Database, what path should be used to save them?
     */
    'attendee_create_or_update_path' => '/api/leads/createOrUpdate',

    /**
     * Additional data to send to the Laravel database.
     */
    'db_attendee_additional_info' => [],

    /**
     * The endpoint to fetch the additional data from the Laravel database.
     * Should be a 'POST' endpoint, and accept an array called 'attendees'
     * and return the same array with the additional data appended to each attendee.
     */
    'fetch_db_attendee_additional_info_path' => null,

    /**
     * Send Google Calendar Event Summary by default?
     */
    'default_event_summary' => false,
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
