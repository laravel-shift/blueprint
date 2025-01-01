<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use function Pest\Faker\fake;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

test('index behaves as expected', function (): void {
    $post = Post::factory()->create();
    $comments = Comment::factory()->count(3)->create();

    $response = get(route('comments.index', ['post' => $post]));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\CommentController::class,
        'store',
        \App\Http\Requests\CommentStoreRequest::class
    );

test('store saves', function (): void {
    $post = Post::factory()->create();
    $body = fake()->text();
    $user = User::factory()->create();

    $response = post(route('comments.store', ['post' => $post]), [
        'body' => $body,
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]);

    $comments = Comment::query()
        ->where('body', $body)
        ->where('post_id', $post->id)
        ->where('user_id', $user->id)
        ->get();
    expect($comments)->toHaveCount(1);
    $comment = $comments->first();

    $response->assertCreated();
    $response->assertJsonStructure([]);
});


test('show behaves as expected', function (): void {
    $comment = Comment::factory()->create();
    $post = Post::factory()->create();

    $response = get(route('comments.show', ['post' => $post, 'comment' => $comment]));

    $response->assertOk();
    $response->assertJsonStructure([]);
});


test('update uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\CommentController::class,
        'update',
        \App\Http\Requests\CommentUpdateRequest::class
    );

test('update behaves as expected', function (): void {
    $comment = Comment::factory()->create();
    $post = Post::factory()->create();
    $body = fake()->text();
    $user = User::factory()->create();

    $response = put(route('comments.update', ['post' => $post, 'comment' => $comment]), [
        'body' => $body,
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]);

    $comment->refresh();

    $response->assertOk();
    $response->assertJsonStructure([]);

    expect($body)->toEqual($comment->body);
    expect($post->id)->toEqual($comment->post_id);
    expect($user->id)->toEqual($comment->user_id);
});


test('destroy deletes and responds with', function (): void {
    $comment = Comment::factory()->create();
    $post = Post::factory()->create();

    $response = delete(route('comments.destroy', ['post' => $post, 'comment' => $comment]));

    $response->assertNoContent();

    assertModelMissing($comment);
});
