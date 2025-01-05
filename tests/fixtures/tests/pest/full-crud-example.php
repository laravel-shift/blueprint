<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Post;
use function Pest\Faker\fake;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

test('index displays view', function (): void {
    $posts = Post::factory()->count(3)->create();

    $response = get(route('posts.index'));

    $response->assertOk();
    $response->assertViewIs('posts.index');
    $response->assertViewHas('posts');
});


test('create displays view', function (): void {
    $response = get(route('posts.create'));

    $response->assertOk();
    $response->assertViewIs('posts.create');
    $response->assertViewHas('post');
});


test('store uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\PostController::class,
        'store',
        \App\Http\Requests\PostStoreRequest::class
    );

test('store saves and redirects', function (): void {
    $title = fake()->sentence(4);
    $content = fake()->paragraphs(3, true);

    $response = post(route('posts.store'), [
        'title' => $title,
        'content' => $content,
    ]);

    $posts = Post::query()
        ->where('title', $title)
        ->where('content', $content)
        ->get();
    expect($posts)->toHaveCount(1);
    $post = $posts->first();

    $response->assertRedirect(route('posts.index'));
});


test('show displays view', function (): void {
    $post = Post::factory()->create();

    $response = get(route('posts.show', $post));

    $response->assertOk();
    $response->assertViewIs('posts.show');
    $response->assertViewHas('post');
});


test('edit displays view', function (): void {
    $post = Post::factory()->create();

    $response = get(route('posts.edit', $post));

    $response->assertOk();
    $response->assertViewIs('posts.edit');
    $response->assertViewHas('post');
});


test('update uses form request validation')
    ->assertActionUsesFormRequest(
        \App\Http\Controllers\PostController::class,
        'update',
        \App\Http\Requests\PostUpdateRequest::class
    );

test('update saves and redirects', function (): void {
    $post = Post::factory()->create();
    $title = fake()->sentence(4);
    $content = fake()->paragraphs(3, true);

    $response = put(route('posts.update', $post), [
        'title' => $title,
        'content' => $content,
    ]);

    $posts = Post::query()
        ->where('title', $title)
        ->where('content', $content)
        ->get();
    expect($posts)->toHaveCount(1);
    $post = $posts->first();

    $response->assertRedirect(route('posts.index'));
});


test('destroy deletes', function (): void {
    $post = Post::factory()->create();

    $response = delete(route('posts.destroy', $post));

    assertModelMissing($post);
});
