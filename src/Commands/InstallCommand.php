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
        $provider = select(
            label: 'Which provider would you like to use?',
            options: ['SendGrid', 'Amazon SES'],
            default: 'SendGrid',
        );

        info("Installing provider: {$provider}");

        // Install the required package
        switch (strtolower($provider)) {
            case 'sendgrid':
                $this->runComposerRequire('sendgrid/sendgrid');
                break;
            case 'amazon ses':
                $this->runComposerRequire('aws/aws-sdk-php');
                break;
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

        // Publish MailerEvent Model
        $this->publishMailerEventModel();

        // Inject `twenty20` mailer into Laravel’s config/mail.php
        $this->updateMailConfig();

        // Update `config/mailer.php` with chosen provider
        $this->updateMailerConfig($provider);

        // Update `.env`
        $this->updateEnv($provider);

        // Run migrations
        $this->call('migrate');

        info('Installation completed.');
        info('Set your API keys in .env and start sending emails!');
    }

    protected function runComposerRequire($package)
    {
        $command = 'composer require ' . $package;
        info("Running: {$command}");
        passthru($command);
    }

    protected function publishMailerEventModel()
    {
        $modelPath = app_path('Models/MailerEvent.php');
        $packageModelPath = __DIR__.'/../../stubs/MailerEvent.php.stub';

        if (File::exists($modelPath)) {
            warning("MailerEvent model already exists in app/Models. Skipping.");
            return;
        }

        if (!File::exists($packageModelPath)) {
            warning("Stub for MailerEvent model not found. Skipping.");
            return;
        }

        File::ensureDirectoryExists(app_path('Models'));
        File::copy($packageModelPath, $modelPath);
        info("Published MailerEvent model to app/Models.");
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
