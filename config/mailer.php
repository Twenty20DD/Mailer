<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mail Provider
    |--------------------------------------------------------------------------
    |
    | The provider in use by your application.
    | Options: "sendgrid", "amazon_ses", "mailchimp", "mailgun"
    |
    */
    'provider' => env('MAILER_PROVIDER', 'sendgrid'),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Each service has its own config sub-array. For example, SendGrid
    | might require an API key and an endpoint, while Amazon SES might
    | rely on AWS credentials.
    |
    */
    'providers' => [

        'sendgrid' => [
            'api_key' => env('SENDGRID_API_KEY', ''),
            'api_url' => env('SENDGRID_API_URL', 'https://api.sendgrid.com/v3/mail/send'),
        ],

        'amazon_ses' => [
            'api_key' => env('AWS_ACCESS_KEY_ID', ''),
            'api_secret' => env('AWS_SECRET_ACCESS_KEY', ''),
            'region' => env('AWS_REGION', 'us-east-1'),
        ],

        'mailchimp' => [
            'api_key' => env('MAILCHIMP_API_KEY', ''),
            'api_url' => env('MAILCHIMP_API_URL', 'https://<REGION>.api.mailchimp.com/3.0/messages/send'),
        ],

        'mailgun' => [
            'api_key' => env('MAILGUN_API_KEY', ''),
            'api_base_url' => env('MAILGUN_API_BASE_URL', 'https://api.mailgun.net/v3'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook URL
    |--------------------------------------------------------------------------
    |
    | If you want to accept webhooks from any provider, define a route path here.
    |
    */
    'webhook_url' => env('MAILER_WEBHOOK_URL', '/sendgrid/webhook'),

];
