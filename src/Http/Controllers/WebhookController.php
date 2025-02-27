<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\MailerEvent;
use App\Events\EmailBounced;
use App\Events\EmailDelivered;
use App\Events\EmailDeferred;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $provider = config('mailer.provider', 'sendgrid');
        $payload = $request->all();

        match (strtolower($provider)) {
            'sendgrid' => $this->handleSendGridEvents($payload),
            // 'amazon_ses' => $this->handleSesEvents($payload),
            default =>throw new Exception("Unknown provider: {$provider}"),
        };

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    protected function handleSendGridEvents(array $payload): void
    {
        foreach ($payload as $event) {
            $mailerEvent = MailerEvent::create([
                'provider' => 'sendgrid',
                'email' => $event['email'] ?? null,
                'event_type' => $event['event'] ?? null,
                'reason' => $event['reason'] ?? null,
                'message_id' => $event['sg_message_id'] ?? null,
                'event_at' => isset($event['timestamp'])
                    ? date('Y-m-d H:i:s', $event['timestamp'])
                    : now(),
            ]);

            match ($mailerEvent->event_type) {
                'bounce' => event(new EmailBounced($mailerEvent->email, 'sendgrid', $mailerEvent->reason)),
                'deferred' => event(new EmailDeferred($mailerEvent->email, 'sendgrid', $mailerEvent->reason)),
                'delivered' => event(new EmailDelivered($mailerEvent->email, 'sendgrid')),
                default => null,
            };
        }
    }

    // TODO: Implement Amazon SES events handling
    // protected function handleSesEvents(array $payload): void
    // {
    //     $rawMessage = $payload['Message'] ?? '{}';
    //     $messageData = json_decode($rawMessage, true);

    //     if (isset($messageData['notificationType'])) {
    //         $type = strtolower($messageData['notificationType']);
    //         $bounce = $messageData['bounce'] ?? [];
    //         $bouncedRecipients = $bounce['bouncedRecipients'] ?? [];

    //         foreach ($bouncedRecipients as $recipient) {
    //             $mailerEvent = MailerEvent::create([
    //                 'provider' => 'amazon_ses',
    //                 'email' => $recipient['emailAddress'] ?? null,
    //                 'event_type' => $type,
    //                 'reason' => $bounce['bounceType'] ?? null,
    //                 'message_id' => $messageData['mail']['messageId'] ?? null,
    //                 'event_at' => now(),
    //             ]);

    //             // Fire Laravel event
    //             event(new EmailBounced($mailerEvent->email, 'amazon_ses', $mailerEvent->reason));
    //         }
    //     }
    // }
}
