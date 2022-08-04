# Benevolent

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

## Use Case

Imagine you built a Laravel-powered website, which needs to communicates to your external API server using REST API. Some of the HTTP requests needs to be authenticated by including access token to the Authorization header. To get the access token, you first authenticate the user to the API server, and then the server return the access token and user data in exchange. You store the access token to use it on every authenticated HTTP requests. All is well until you realize, that default Laravel authentication middleware cannot authenticate user to another server leveraging REST API, and you need to write new authentication logics to cover those requirements...

That's why this package exist. Just install this package, set the config, and you all done!

## Installation

### Laravel

Require this package in the project.

```bash
$ composer require elzdave/benevolent
```

Publish the config after installation.

```bash
$ php artisan vendor:publish --tag=benevolent-config
```

## Usage

### User Authentication

1. At the `config/auth.php`, change the `provider` entry on `guards.web` to `benevolent`
2. Add the new entry on `.env` and `.env.example`, change `<your-external-API-base-URI>` to your authentication server's base URI, eg: https://server.api/v1

```env
EXT_API_BASE_URI=<your-external-API-base-URI>
```

3. Optional: adjust some settings to suit your API server design at `config/benevolent.php`

### Making Authenticated HTTP Request using Authorization header

If you want to make authenticated HTTP requests using the user's access token, use the `Elzdave\Benevolent\Http\Http` facade with `useAuth()` method

```php
<?php

use Elzdave\Benevolent\Http\Http;

$data = [
    'data' => 'some-data-to-store'
];

$apiRelativeUrl = 'path/relative/to/base-uri';
$apiResponse = Http::useAuth()->post($apiRelativeUrl, $data);
```

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

```bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email the author instead of using the issue tracker.

## Credits

- [David Eleazar](link-author)
- [All contributors](link-contributors)

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/elzdave/benevolent.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/elzdave/benevolent.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/elzdave/benevolent/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield
[link-packagist]: https://packagist.org/packages/elzdave/benevolent
[link-downloads]: https://packagist.org/packages/elzdave/benevolent
[link-travis]: https://travis-ci.org/elzdave/benevolent
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/elzdave
[link-contributors]: ../../contributors
