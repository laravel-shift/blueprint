<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Post;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PostController
 */
class PostControllerTest extends TestCase
{
    /**
     * @test
     */
    public function show_displays_view(): void
    {
        $post = Post::factory()->create();

        $response = $this->get(route('post.show', $post));

        $response->assertOk();
        $response->assertViewIs('posts.show');
        $response->assertViewHas('post');
    }
}
