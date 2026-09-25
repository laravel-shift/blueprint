<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Post;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\get;

test('show displays view', function (): void {
    $post = Post::factory()->create();

    $response = get(route('posts.show', $post));

    $response->assertOk();
    $response->assertViewIs('post.show');
    $response->assertViewHas('post', $post);
});


test('publish saves and redirects', function (): void {
    $post = Post::factory()->create();

    $response = get(route('posts.publish', $post));

    $response->assertRedirect(route('posts.show', ['post' => $post]));

    assertDatabaseHas('posts', [ /* ... */ ]);
});


test('archive deletes and redirects', function (): void {
    $post = Post::factory()->create();

    $response = get(route('posts.archive', $post));

    $response->assertRedirect(route('posts.index'));

    assertModelMissing($post);
});
