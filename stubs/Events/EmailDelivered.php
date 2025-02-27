<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailDelivered
{
    use Dispatchable, SerializesModels;

    public string $email;
    public string $provider;

    public function __construct(string $email, string $provider)
    {
        $this->email = $email;
        $this->provider = $provider;
    }
}
