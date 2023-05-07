<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Post;
use function Pest\Faker\fake;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('index responds with', function (): void {
    $posts = Post::factory()->count(3)->create();

    $response = get(route('post.index'));

    $response->assertOk();
    $response->assertJson($posts);
});


test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\Api\PostController::class,
        'store',
        \App\Http\Requests\Api\PostStoreRequest::class
    );

test('store responds with', function (): void {
    $title = fake()->sentence(4);

    $response = post(route('post.store'), [
        'title' => $title,
    ]);

    $response->assertNoContent();
});


test('error responds with', function (): void {
    $response = get(route('post.error'));

    $response->assertNoContent(400);
});
