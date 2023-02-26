<?php

namespace Some\App\Events;

use Illuminate\Queue\SerializesModels;

class NewPost
{
    use SerializesModels;

    public $post;

    /**
     * Create a new event instance.
     */
    public function __construct($post)
    {
        $this->post = $post;
    }
}
