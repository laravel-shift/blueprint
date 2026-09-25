<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PostController
 */
final class PostControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function publish_redirects(): void
    {
        $post = Post::factory()->create();

        $response = $this->get(route('posts.publish', $post));

        $post->refresh();

        $response->assertRedirect(route('posts.index'));
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PostController::class,
            'update',
            \App\Http\Requests\PostUpdateRequest::class
        );
    }

    #[Test]
    public function update_redirects(): void
    {
        $post = Post::factory()->create();
        $title = fake()->sentence(4);
        $content = fake()->paragraphs(3, true);

        $response = $this->put(route('posts.update', $post), [
            'title' => $title,
            'content' => $content,
        ]);

        $post->refresh();

        $response->assertRedirect(route('posts.index'));

        $this->assertEquals($title, $post->title);
        $this->assertEquals($content, $post->content);
    }
}
