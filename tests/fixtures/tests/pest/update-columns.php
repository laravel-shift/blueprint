<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Post;
use function Pest\Faker\fake;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

test('publish redirects', function (): void {
    $post = Post::factory()->create();

    $response = get(route('posts.publish', $post));

    $post->refresh();

    $response->assertRedirect(route('posts.index'));
});


test('update uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\PostController::class,
        'update',
        \App\Http\Requests\PostUpdateRequest::class
    );

test('update redirects', function (): void {
    $post = Post::factory()->create();
    $title = fake()->sentence(4);
    $content = fake()->paragraphs(3, true);

    $response = put(route('posts.update', $post), [
        'title' => $title,
        'content' => $content,
    ]);

    $post->refresh();

    $response->assertRedirect(route('posts.index'));

    expect($title)->toEqual($post->title);
    expect($content)->toEqual($post->content);
});
