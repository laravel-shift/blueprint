<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Post;
use function Pest\Laravel\get;

test('show displays view', function (): void {
    $post = Post::factory()->create();

    $response = get(route('posts.show', $post));

    $response->assertOk();
    $response->assertViewIs('posts.show');
    $response->assertViewHas('post');
});
