[![Latest Version on Packagist](https://img.shields.io/packagist/v/twenty20/mailer.svg?style=flat-square)](https://packagist.org/packages/twenty20/mailer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/:vendor_slug/:package_slug/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/:vendor_slug/:package_slug/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/twenty20/mailer.svg?style=flat-square)](https://packagist.org/packages/twenty20/mailer)

## Mailer Package

A multi-provider mailer package for SendGrid, Amazon SES.

reply to header
cc header
bcc

notifications laravel

fire off events to track
MailSends model/table

create a listener in the install command to listen for events to update MailSends

Keep new DB table for event tracking

test for failures if API ley is missing, tests in general.


### Installation

You can install the package via composer:

```bash
composer require twenty20/mailer
```

You can publish and run the migrations with:

```bash
php artisan mailer:install
```

One command to fully ensure the package is up and running in no time at all.


Publish the config

```bash
php artisan vendor:publish --tag=mailer-config
```

### Usage

The API remains the same regardless of the provider you choose.

```php
use Twenty20\Mailer\Mail\Mailer;

Route::get('/send-test', function (Mailer $mailer) {
    $response = $mailer->sendMail(
        to: 'john@example.com',
        from: 'no-reply@myapp.com',
        subject: 'Hello from My App',
        body: '<p>This is a test email.</p>'
    );

    dd($response);
});
```


### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

### Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

### Credits

- [Twenty20](https://github.com/Twnety20)
- [All Contributors](../../contributors)

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
