<?php

namespace App\Listeners;

use App\Events\EmailDeferred;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class EmailDeferredListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(EmailDeferred $event): void
    {
        // Log the bounced email
        Log::warning("Email deferred: {$event->email} | Provider: {$event->provider} | Reason: {$event->reason}");

        // Here you can implement additional logic, such as:
        // - Marking the email as invalid in the database
        // - Sending a notification to the user/admin
        // - Updating the user's status if needed
    }
}
