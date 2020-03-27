<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\PostController
 */
class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_responds_with()
    {
        $posts = factory(Post::class, 3)->create();

        $response = $this->get(route('post.index'));

        $response->assertOk();
        $response->assertJson($posts);
    }


    /**
     * @test
     */
    public function store_responds_with()
    {
        $response = $this->post(route('post.store'));

        $response->assertNoContent();
    }


    /**
     * @test
     */
    public function error_responds_with()
    {
        $response = $this->get(route('post.error'));

        $response->assertNoContent(400);
    }
}
