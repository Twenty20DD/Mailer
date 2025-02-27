[![Latest Version on Packagist](https://img.shields.io/packagist/v/twenty20/mailer.svg?style=flat-square)](https://packagist.org/packages/twenty20/mailer)
[![Total Downloads](https://img.shields.io/packagist/dt/twenty20/mailer.svg?style=flat-square)](https://packagist.org/packages/twenty20/mailer)

## Mailer Package

A multi-provider mailer package supporting SendGrid and Amazon SES.

### Installation

Install the package via Composer:

```bash
composer require twenty20/mailer
```

Once installed, run the following command to complete the setup:

```bash
php artisan mailer:install
```

Specify the webhook URL in your choosen provider to:

```bash
http://yourdomain.com/mailer/webhook
```

This should already be injected into your web routes files from the package.

### Usage

The package provides Events & Listeners.

Register these listeners in EventServiceProvider.php

```php
protected $listen = [
    EmailBounced::class => [
        EmailBouncedListener::class,
    ],
    EmailDelivered::class => [
        EmailDeliveredListener::class,
    ],
    EmailDeferred::class => [
        EmailDeferredListener::class,
    ],
];
```

For Laravel 12 you can register events and their corresponding listeners within the `boot` method of your application's `AppServiceProvider`:

```php
public function boot(): void
{
     Event::listen(
        EmailBounced::class,
        EmailBouncedListener::class,
        ...
    );
}
```

These events will be triggered from the WebhookController that package also installs.

Each listeners show an example how you can use this.

```php
Log::warning("Email bounced: {$event->email} | Provider: {$event->provider} | Reason: {$event->reason}");

// Here you can implement additional logic, such as:
// - Marking the email as invalid in the database
// - Sending a notification to the user/admin
// - Updating the user's status if needed
```

Lastly within your VerifyCSRFToken middleware, add the following:

```php
protected $except = [
    'mailer/*',
];
```

Or if using a later version of Laravel, in `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'mailer/*',
    ]);
})->withExceptions(function (Exceptions $exceptions) {
    //
})->create();
```

After installation, add your chosen provider’s API key to the `.env` file, and you’re good to go.

You can then send emails and notifications as usual within your Laravel application.

---

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

---

### Credits

- [Twenty20](https://github.com/Twnety20)
- [All Contributors](../../contributors)

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
