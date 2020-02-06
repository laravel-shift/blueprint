<?php

namespace Tests\Feature\Http\Controllers;

use App\Events\NewPost;
use App\Jobs\SyncMedia;
use App\Mail\ReviewNotification;
use App\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use JMac\Testing\Traits\HttpTestAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PostController
 */
class PostControllerTest extends TestCase
{
    use HttpTestAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function index_displays_view()
    {
        $posts = factory(Post::class, 3)->create();

        $response = $this->get(route('post.index'));

        $response->assertOk();
        $response->assertViewIs('post.index');
        $response->assertViewHas('posts');
    }


    /**
     * @test
     */
    public function store_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PostController::class,
            'store',
            \App\Http\Requests\PostStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves_and_redirects()
    {
        $title = $this->faker->sentence(4);
        $content = $this->faker->paragraphs(3, true);

        Mail::fake();
        Queue::fake();
        Event::fake();

        $response = $this->post(route('post.store'), [
            'title' => $title,
            'content' => $content,
        ]);

        $posts = Post::query()
            ->where('title', $title)
            ->where('content', $content)
            ->get();
        $this->assertCount(1, $posts);
        $post = $posts->first();

        $response->assertRedirect(route('post.index'));
        $response->assertSessionHas('post.title', $post->title);

        Mail::assertSent(ReviewNotification::class, function ($mail) use ($post) {
            return $mail->hasTo($post->author) && $mail->post->is($post);
        });
        Queue::assertPushed(SyncMedia::class, function ($job) use ($post) {
            return $job->post->is($post);
        });
        Event::assertDispatched(NewPost::class, function ($event) use ($post) {
            return $event->post->is($post);
        });
    }
}
