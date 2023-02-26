<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Post::where('title', $title)->where('content', $content)->orderBy('published_at')->limit(5)->get();

        return view('post.index', compact('posts'));
    }

    public function edit(Request $request, Post $post): View
    {
        $post = Post::find($id);

        return view('post.edit', compact('post'));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $post = Post::find($id);

        $post_ids = Post::where('title', $post->title)->take(3)->pluck('id');

        $post->save();

        return redirect()->route('post.edit', ['post' => $post]);
    }
}
