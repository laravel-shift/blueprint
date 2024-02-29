<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostStoreRequest;
use App\Http\Requests\PostUpdateRequest;
use App\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Post::class, 'post');
    }

    public function index(Request $request): View
    {
        $posts = Post::all();

        return view('post.index', compact('posts'));
    }

    public function create(Request $request): View
    {
        return view('post.create');
    }

    public function store(PostStoreRequest $request): RedirectResponse
    {
        $post = Post::create($request->validated());

        $request->session()->flash('post.id', $post->id);

        return redirect()->route('posts.index');
    }

    public function show(Request $request, Post $post): View
    {
        return view('post.show', compact('post'));
    }

    public function edit(Request $request, Post $post): View
    {
        return view('post.edit', compact('post'));
    }

    public function update(PostUpdateRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->validated());

        $request->session()->flash('post.id', $post->id);

        return redirect()->route('posts.index');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('posts.index');
    }
}
