<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class UserCreated
{
    use SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
