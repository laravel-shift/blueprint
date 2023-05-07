<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\PostController
 */
class PostControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function index_responds_with(): void
    {
        $posts = Post::factory()->count(3)->create();

        $response = $this->get(route('post.index'));

        $response->assertOk();
        $response->assertJson($posts);
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\PostController::class,
            'store',
            \App\Http\Requests\Api\PostStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_responds_with(): void
    {
        $title = $this->faker->sentence(4);

        $response = $this->post(route('post.store'), [
            'title' => $title,
        ]);

        $response->assertNoContent();
    }


    /**
     * @test
     */
    public function error_responds_with(): void
    {
        $response = $this->get(route('post.error'));

        $response->assertNoContent(400);
    }
}
