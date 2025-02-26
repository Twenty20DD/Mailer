<?php

namespace Twenty20\Mailer;

use Spatie\LaravelPackageTools\Package;
use Twenty20\Mailer\Commands\InstallCommand;
use Twenty20\Mailer\Transport\Twenty20Transport;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Support\Facades\Mail;

class MailerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('mailer')
            ->hasConfigFile('mailer')
            ->hasRoutes('web')
            ->hasMigration('create_mailer_table')
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton(Mailer::class, function ($app) {
            return new Mailer($app['config']->get('mailer', []));
        });
    }

    public function boot()
    {
        parent::boot();

        // publish the config file
        $this->publishes([
            __DIR__.'/../config/mailer.php' => config_path('mailer.php'),
        ], 'mailer-config');

        Mail::extend('twenty20', function () {
            return new Twenty20Transport(app(Mailer::class));
        });
    }
}
