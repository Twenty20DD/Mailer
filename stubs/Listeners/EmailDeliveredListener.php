<?php

namespace App\Listeners;

use App\Events\EmailDelivered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class EmailDeliveredListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(EmailDelivered $event): void
    {
        // Log the delivered email
        Log::info("Email delivered: {$event->email} | Provider: {$event->provider}");

        // Here you can implement additional logic, such as:
        // - Updating email status in the database
        // - Sending a confirmation to the sender
        // - Notifying the user that their email was successfully sent
    }
}
