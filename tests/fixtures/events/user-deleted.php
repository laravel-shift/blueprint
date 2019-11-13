<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class UserDeleted
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
}
