<?php

namespace Tests\Feature\Http\Controllers;

use App\Events\NewPost;
use App\Jobs\SyncMedia;
use App\Mail\ReviewPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use function Pest\Faker\fake;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('index displays view', function (): void {
    $posts = Post::factory()->count(3)->create();

    $response = get(route('posts.index'));

    $response->assertOk();
    $response->assertViewIs('post.index');
    $response->assertViewHas('posts');
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
    $author = User::factory()->create();

    Mail::fake();
    Queue::fake();
    Event::fake();

    $response = post(route('posts.store'), [
        'title' => $title,
        'content' => $content,
        'author_id' => $author->id,
    ]);

    $posts = Post::query()
        ->where('title', $title)
        ->where('content', $content)
        ->where('author_id', $author->id)
        ->get();
    expect($posts)->toHaveCount(1);
    $post = $posts->first();

    $response->assertRedirect(route('posts.index'));
    $response->assertSessionHas('post.title', $post->title);

    Mail::assertSent(ReviewPost::class, function ($mail) use ($post) {
        return $mail->hasTo($post->author->email) && $mail->post->is($post);
    });
    Queue::assertPushed(SyncMedia::class, function ($job) use ($post) {
        return $job->post->is($post);
    });
    Event::assertDispatched(NewPost::class, function ($event) use ($post) {
        return $event->post->is($post);
    });
});
