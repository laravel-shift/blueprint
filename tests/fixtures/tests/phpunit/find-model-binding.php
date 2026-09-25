<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PostController
 */
final class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function show_displays_view(): void
    {
        $post = Post::factory()->create();

        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertViewIs('post.show');
        $response->assertViewHas('post', $post);
    }


    #[Test]
    public function publish_saves_and_redirects(): void
    {
        $post = Post::factory()->create();

        $response = $this->get(route('posts.publish', $post));

        $response->assertRedirect(route('posts.show', ['post' => $post]));

        $this->assertDatabaseHas('posts', [ /* ... */ ]);
    }


    #[Test]
    public function archive_deletes_and_redirects(): void
    {
        $post = Post::factory()->create();

        $response = $this->get(route('posts.archive', $post));

        $response->assertRedirect(route('posts.index'));

        $this->assertModelMissing($post);
    }


    #[Test]
    public function assign_displays_view(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('posts.assign'));

        $response->assertOk();
        $response->assertViewIs('post.assign');
        $response->assertViewHas('user', $user);
    }
}
