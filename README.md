[![Latest Version on Packagist](https://img.shields.io/packagist/v/twenty20/mailer.svg?style=flat-square)](https://packagist.org/packages/twenty20/mailer)
[![Total Downloads](https://img.shields.io/packagist/dt/twenty20/mailer.svg?style=flat-square)](https://packagist.org/packages/twenty20/mailer)

## Mailer Package

A multi-provider mailer package for SendGrid, Amazon SES.

### Installation

You can install the package via composer:

```bash
composer require twenty20/mailer
```

After composer, you can run the following command, this will install everything you need.

```bash
php artisan mailer:install
```

### Usage

Once the installer is complete, simply add your API key for your choosen provider to the `.env` and you're all set.
Send emails and notifications as you would normally within your Laravel app.

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

### Credits

- [Twenty20](https://github.com/Twnety20)
- [All Contributors](../../contributors)

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
