<?php

namespace App\Http\Controllers;

use App\Events\NewPost;
use App\Http\Requests\PostStoreRequest;
use App\Jobs\SyncMedia;
use App\Mail\ReviewPost;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PostController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $posts = Post::all();

        return view('post.index', compact('posts'));
    }

    /**
     * @param \App\Http\Requests\PostStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(PostStoreRequest $request)
    {
        $post = Post::create($request->validated());

        Mail::to($post->author->email)->send(new ReviewPost($post));

        SyncMedia::dispatch($post);

        event(new NewPost($post));

        $request->session()->flash('post.title', $post->title);

        return redirect()->route('post.index');
    }
}
