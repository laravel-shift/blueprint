<?php

namespace Some\App\Events;

use Illuminate\Queue\SerializesModels;

class NewPost
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public $post)
    {
        //
    }
}
