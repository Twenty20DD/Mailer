<?php

namespace Twenty20\Mailer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    protected $signature = 'mailer:install';
    protected $description = 'Install and configure the desired mail service provider.';

    public function handle()
    {
        // 'Amazon SES'
        $provider = select(
            label: 'Which provider would you like to use?',
            options: ['SendGrid'],
            default: 'SendGrid',
        );

        info("Installing provider: {$provider}");

        // Install the required package
        switch (strtolower($provider)) {
            case 'sendgrid':
                $this->runComposerRequire('sendgrid/sendgrid');
                break;
            // case 'amazon ses': TODO
            //     $this->runComposerRequire('aws/aws-sdk-php');
            //     break;
            default:
                warning("Unknown provider [{$provider}]. Defaulting to SendGrid.");
                $provider = 'SendGrid';
                $this->runComposerRequire('sendgrid/sendgrid');
                break;
        }

        // Auto-publish the package config
        $this->call('vendor:publish', [
            '--tag' => 'mailer-config',
            '--force' => true,
        ]);

        // Publish WebhookController
        $this->publishMailerController();

        // Publish Events and Listeners
        $this->publishEventsListeners();

        //Publish Routes
        $this->publishRoutes();

        // Inject `twenty20` mailer into Laravel’s config/mail.php
        $this->updateMailConfig();

        // Update `config/mailer.php` with chosen provider
        $this->updateMailerConfig($provider);

        // Update `.env`
        $this->updateEnv($provider);

        // Run migrations
        $this->callSilent('migrate');

        $this->callSilent('optimize:clear');

        $this->callSilent('cache:clear');

        $this->callSilent('config:clear');

        $this->callSilent('route:clear');

        info('🎉 Installation completed. Set your API keys in .env and start sending emails!');
    }

    protected function runComposerRequire($package)
    {
        $command = 'composer require ' . $package;
        info("Running: {$command}");
        passthru($command);
    }

    protected function publishRoutes()
    {
        $routesPath = base_path('routes/web.php');
        $packageRoutesPath = __DIR__.'/routes/web.php';

        if (File::exists($routesPath)) {
            warning("Webhook route already exists in routes/web.php. Skipping.");
            return;
        }

        if (!File::exists($packageRoutesPath)) {
            warning("Stub for Webhook route not found. Skipping.");
            return;
        }

        File::ensureDirectoryExists(base_path('routes'));
        File::copy($packageRoutesPath, $routesPath);
        info("Published Webhook route to routes/web.php.");
    }

    protected function publishMailerController()
    {
        $controllerPath = app_path('Http/Controllers/WebhookController.php');
        $packageControllerPath = __DIR__.'/../../stubs/WebhookController.php.stub';

        if (File::exists($controllerPath)) {
            warning("WebhookController already exists in app/Http/Controllers. Skipping.");
            return;
        }

        if (!File::exists($packageControllerPath)) {
            warning("Stub for WebhookController not found. Skipping.");
            return;
        }

        File::ensureDirectoryExists(app_path('Http/Controllers'));
        File::copy($packageControllerPath, $controllerPath);
        info("Published WebhookController to app/Http/Controllers.");
    }

    protected function publishEventsListeners()
    {
        $stubPath = __DIR__ . '/../../stubs';
        $eventStubPath = $stubPath . '/Events';
        $listenerStubPath = $stubPath . '/Listeners';

        $eventTargetPath = app_path('Events');
        $listenerTargetPath = app_path('Listeners');

        // Ensure directories exist
        File::ensureDirectoryExists($eventTargetPath);
        File::ensureDirectoryExists($listenerTargetPath);

        // Copy events
        $eventFiles = File::files($eventStubPath);
        foreach ($eventFiles as $file) {
            File::copy($file->getRealPath(), $eventTargetPath . '/' . $file->getFilename());
            info("Copied: {$file->getFilename()} to Events directory.");
        }

        // Copy listeners
        $listenerFiles = File::files($listenerStubPath);
        foreach ($listenerFiles as $file) {
            File::copy($file->getRealPath(), $listenerTargetPath . '/' . $file->getFilename());
            info("Copied: {$file->getFilename()} to Listeners directory.");
        }
    }

    protected function updateMailConfig()
    {
        $configFile = config_path('mail.php');

        if (! File::exists($configFile)) {
            warning("Laravel mail config file not found at [{$configFile}]. Skipping mail config update.");
            return;
        }

        $contents = File::get($configFile);
        $pattern = "/'mailers' => \[/";
        if (Str::contains($contents, "'twenty20' =>")) {
            info("twenty20 mailer already exists in mail.php.");
            return;
        }

        $replacement = "'mailers' => [\n        'twenty20' => [\n            'transport' => 'twenty20',\n        ],";
        $newContents = preg_replace($pattern, $replacement, $contents);

        if ($newContents) {
            File::put($configFile, $newContents);
            info("Updated 'twenty20' mailer to config/mail.php.");
        } else {
            warning("Failed to modify config/mail.php.");
        }
    }

    protected function updateMailerConfig(string $provider)
    {
        $configFile = config_path('mailer.php');

        if (! File::exists($configFile)) {
            warning("Config file not found at [{$configFile}]. Skipping update.");
            return;
        }

        $contents = File::get($configFile);
        $pattern = "/('provider' => env\('MAILER_PROVIDER', ')[^']+('\))/";
        $replacement = "'provider' => env('MAILER_PROVIDER', '".strtolower($provider)."')";

        $newContents = preg_replace($pattern, $replacement, $contents);

        File::put($configFile, $newContents);
        info("Updated config/mailer.php to provider {$provider}");
    }

    protected function updateEnv(string $provider)
    {
        $envFiles = [
            base_path('.env'),
            base_path('.env.example')
        ];

        foreach ($envFiles as $filePath) {
            if (!File::exists($filePath)) {
                warning("File not found: {$filePath}. Skipping update.");
                continue;
            }

            $envContents = File::get($filePath);
            $envContents = $this->replaceEnvVariable($envContents, 'MAIL_MAILER', 'twenty20');
            $envContents = $this->appendProviderKeys($provider, $envContents);

            File::put($filePath, $envContents);
            info("Updated {$filePath} with placeholders for {$provider}.");
        }
    }

    protected function replaceEnvVariable($envContents, $key, $newValue)
    {
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}={$newValue}";

        if (preg_match($pattern, $envContents)) {
            return preg_replace($pattern, $replacement, $envContents);
        }

        return $envContents . PHP_EOL . $replacement;
    }

    protected function appendProviderKeys($provider, $envContents)
    {
        $placeholders = [];

        switch ($provider) {
            case 'sendgrid':
                $placeholders = [
                    'MAILER_PROVIDER=sendgrid',
                    'SENDGRID_API_KEY=',
                    'SENDGRID_API_URL=https://api.sendgrid.com/v3/mail/send',
                ];
                break;

            case 'amazon ses':
                $placeholders = [
                    'MAILER_PROVIDER=amazon_ses',
                    'AWS_ACCESS_KEY_ID=',
                    'AWS_SECRET_ACCESS_KEY=',
                    'AWS_REGION=us-east-1',
                ];
                break;
        }

        if (empty($placeholders)) {
            $placeholders = [
                'MAILER_PROVIDER=sendgrid',
                'SENDGRID_API_KEY=',
                'SENDGRID_API_URL=https://api.sendgrid.com/v3/mail/send',
            ];
        }

        foreach ($placeholders as $line) {
            if (! Str::contains($envContents, $line)) {
                $envContents .= PHP_EOL . $line;
            }
        }

        return $envContents;
    }
}
