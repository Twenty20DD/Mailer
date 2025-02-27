<?php

namespace Twenty20\Mailer;

use Aws\Sdk as AwsSdk;
use Aws\Ses\SesClient;
use Illuminate\Support\Facades\Http;
use SendGrid\Mail\Mail as SendGridMail;

class Mailer
{
    protected string $provider;

    protected array $providerConfig;

    // protected ?SesClient $sesClient;


    public function __construct(array $config)
    {
        $this->provider = $config['provider'] ?? 'sendgrid';
        $this->providerConfig = $config['providers'][$this->provider] ?? [];

        if (!isset($this->providerConfig['api_key'])) {
            throw new \RuntimeException("Mailer provider [{$this->provider}] is missing API key configuration.");
        }
    }

    public function sendMail(string $to, string $from, string $subject, string $body, array $cc = [], array $bcc = [], array $replyTo = [])
    {
        return match ($this->provider) {
            'sendgrid' => $this->sendViaSendGrid($to, $from, $subject, $body, $cc, $bcc, $replyTo),
            // 'amazon_ses' => $this->sendViaAmazonSes($to, $from, $subject, $body, $cc, $bcc, $replyTo),
            default => throw new \RuntimeException("Unsupported provider [{$this->provider}]."),
        };
    }

    protected function sendViaSendGrid(string $to, string $from, string $subject, string $body, array $cc, array $bcc, array $replyTo)
    {
        $email = new SendGridMail();
        $email->setFrom($from);
        $email->setSubject($subject);
        $email->addTo($to);
        $email->addContent('text/plain', strip_tags($body));
        $email->addContent('text/html', $body);

        foreach ($cc as $ccEmail) {
            $email->addCc($ccEmail);
        }

        foreach ($bcc as $bccEmail) {
            $email->addBcc($bccEmail);
        }

        foreach ($replyTo as $replyEmail) {
            $email->setReplyTo($replyEmail);
        }

        $response = Http::withToken($this->providerConfig['api_key'])
            ->post($this->providerConfig['api_url'], $email->jsonSerialize());


        if ($response->failed()) {
            throw new \RuntimeException($response->json()['errors'][0]['message'] ?? 'Unknown error');
        }

        return $response->json();
    }

    // protected function sendViaAmazonSes(string $to, string $from, string $subject, string $body, array $cc, array $bcc, array $replyTo)
    // {
    //     $sdk = new \Aws\Sdk([
    //         'region' => $this->providerConfig['region'] ?? 'us-east-1',
    //         'version' => 'latest',
    //         'credentials' => [
    //             'key' => $this->providerConfig['api_key'] ?? '',
    //             'secret' => $this->providerConfig['api_secret'] ?? '',
    //         ],
    //     ]);

    //     $sesClient = $sdk->createSes();

    //     $message = [
    //         'Source' => $from,
    //         'Destination' => [
    //             'ToAddresses' => [$to],
    //             'CcAddresses' => $cc,
    //             'BccAddresses' => $bcc,
    //         ],
    //         'ReplyToAddresses' => $replyTo,
    //         'Message' => [
    //             'Subject' => [
    //                 'Data' => $subject,
    //             ],
    //             'Body' => [
    //                 'Text' => [
    //                     'Data' => strip_tags($body),
    //                 ],
    //                 'Html' => [
    //                     'Data' => $body,
    //                 ],
    //             ],
    //         ],
    //     ];

    //     $result = $sesClient->sendEmail($message);

    //     return $result->toArray();
    // }
}
