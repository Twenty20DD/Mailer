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

### Usage

After installation, add your chosen provider’s API key to the .env file, and you’re good to go.
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

### Credits

- [Twenty20](https://github.com/Twnety20)
- [All Contributors](../../contributors)

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
