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

        $this->publishEventsListeners();

        // Copy the Webhook Controller
        $this->publishMailerController();

        // Inject the webhook route into web.php
        $this->publishRoutes();

        // Inject `twenty20` mailer into Laravel’s config/mail.php
        $this->updateMailConfig();

        // Update `config/mailer.php` with chosen provider
        $this->updateMailerConfig($provider);

        // Update `.env`
        $this->updateEnv($provider);

        // Run migrations
        $this->callSilent('migrate');


        passthru('composer dump-autoload');

        $this->callSilent('cache:clear');
        $this->callSilent('config:clear');

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
        $routeFilePath = base_path('routes/web.php');
        $stubPath = __DIR__ . '/../../stubs/web.php.stub';

        if (!File::exists($routeFilePath)) {
            warning('routes/web.php not found. Skipping route injection.');
            return;
        }

        $currentRoutes = File::get($routeFilePath);
        $stubContents = File::get($stubPath);

        preg_match('/^use .*;$/m', $stubContents, $useStatements);
        preg_match('/Route::.*;$/m', $stubContents, $routeStatements);

        $useStatement = !empty($useStatements) ? $useStatements[0] : null;
        $routeDefinition = !empty($routeStatements) ? $routeStatements[0] : null;

        if ($useStatement && !str_contains($currentRoutes, $useStatement)) {
            if (preg_match('/^(<\?php\s*\n)(.*?)(\n\n|$)/s', $currentRoutes, $matches)) {
                $existingUses = $matches[2];
                $newUses = trim($existingUses . PHP_EOL . $useStatement);

                $currentRoutes = preg_replace('/^(<\?php\s*\n)(.*?)(\n\n|$)/s', "<?php\n" . $newUses . "\n\n", $currentRoutes, 1);
            } else {

                $currentRoutes = preg_replace('/<\?php\s*\n/', "<?php\n\n" . $useStatement . "\n", $currentRoutes, 1);
            }
        }

        if ($routeDefinition && !str_contains($currentRoutes, $routeDefinition)) {
            $currentRoutes .= PHP_EOL . PHP_EOL . $routeDefinition;
        }

        File::put($routeFilePath, $currentRoutes);
        info('Webhook route successfully added to routes/web.php');
    }

    protected function publishMailerController()
    {
        $stubPath = __DIR__ . '/../../stubs/WebhookController.php.stub';
        $targetPath = app_path('Http/Controllers/WebhookController.php');
        if (!File::exists($targetPath)) {
            File::copy($stubPath, $targetPath);
            info('WebhookController.php has been published to app/Http/Controllers');
        } else {
            warning('WebhookController.php already exists. Skipping.');
        }

        info("Published WebhookController to app/Http/Controllers.");
    }

    protected function publishEventsListeners()
    {
        $stubPath = __DIR__ . '/../../stubs';
        $eventStubPath = $stubPath . '/events';
        $listenerStubPath = $stubPath . '/listeners';

        $eventTargetPath = app_path('Events');
        $listenerTargetPath = app_path('Listeners');

        File::ensureDirectoryExists($eventTargetPath);
        File::ensureDirectoryExists($listenerTargetPath);

        $eventFiles = File::files($eventStubPath);
        foreach ($eventFiles as $file) {
            $fileContents = File::get($file->getRealPath());

            $fileContents = str_replace('NamespacePlaceholder', 'App\Events', $fileContents);

            // Remove .stub extension from filename
            $fileName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $fileName = str_replace('.stub', '', $fileName);

            $targetFile = $eventTargetPath . '/' . $fileName;

            File::put($targetFile, $fileContents);

            $this->info("Copied: {$file->getFilename()} as " . basename($targetFile));
        }

        $listenerFiles = File::files($listenerStubPath);
        foreach ($listenerFiles as $file) {
            $fileContents = File::get($file->getRealPath());

            $fileContents = str_replace('NamespacePlaceholder', 'App\Listeners', $fileContents);

            // Remove .stub extension from filename
            $fileName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $fileName = str_replace('.stub', '', $fileName);

            $targetFile = $listenerTargetPath . '/' . $fileName;

            File::put($targetFile, $fileContents);

            $this->info("Copied: {$file->getFilename()} as " . basename($targetFile));
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
