<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\CommentController
 */
final class CommentControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $post = Post::factory()->create();
        $comments = Comment::factory()->count(3)->create();

        $response = $this->get(route('comments.index', ['post' => $post]));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CommentController::class,
            'store',
            \App\Http\Requests\CommentStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $post = Post::factory()->create();
        $body = fake()->text();
        $user = User::factory()->create();

        $response = $this->post(route('comments.store', ['post' => $post]), [
            'body' => $body,
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $comments = Comment::query()
            ->where('body', $body)
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->get();
        $this->assertCount(1, $comments);
        $comment = $comments->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $comment = Comment::factory()->create();
        $post = Post::factory()->create();

        $response = $this->get(route('comments.show', ['post' => $post, 'comment' => $comment]));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\CommentController::class,
            'update',
            \App\Http\Requests\CommentUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $comment = Comment::factory()->create();
        $post = Post::factory()->create();
        $body = fake()->text();
        $user = User::factory()->create();

        $response = $this->put(route('comments.update', ['post' => $post, 'comment' => $comment]), [
            'body' => $body,
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $comment->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($body, $comment->body);
        $this->assertEquals($post->id, $comment->post_id);
        $this->assertEquals($user->id, $comment->user_id);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $comment = Comment::factory()->create();
        $post = Post::factory()->create();

        $response = $this->delete(route('comments.destroy', ['post' => $post, 'comment' => $comment]));

        $response->assertNoContent();

        $this->assertModelMissing($comment);
    }
}
