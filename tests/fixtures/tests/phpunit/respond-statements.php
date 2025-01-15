<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\PostController
 */
final class PostControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $posts = Post::factory()->count(3)->create();

        $response = $this->get(route('posts.index'));

        $response->assertOk();
        $response->assertJson($posts);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\PostController::class,
            'store',
            \App\Http\Requests\Api\PostStoreRequest::class
        );
    }

    #[Test]
    public function store_responds_with(): void
    {
        $title = fake()->sentence(4);

        $response = $this->post(route('posts.store'), [
            'title' => $title,
        ]);

        $response->assertNoContent();
    }


    #[Test]
    public function error_responds_with(): void
    {
        $response = $this->get(route('posts.error'));

        $response->assertNoContent(400);
    }
}
