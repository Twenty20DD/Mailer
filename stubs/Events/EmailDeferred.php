<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailDeferred
{
    use Dispatchable, SerializesModels;

    public string $email;
    public string $provider;
    public ?string $reason;

    public function __construct(string $email, string $provider, ?string $reason)
    {
        $this->email = $email;
        $this->provider = $provider;
        $this->reason = $reason;
    }
}
