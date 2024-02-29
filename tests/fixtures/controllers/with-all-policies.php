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
    public function index(Request $request): View
    {
        $this->authorize('index', Post::class);

        $posts = Post::all();

        return view('post.index', compact('posts'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Post::class);

        return view('post.create');
    }

    public function store(PostStoreRequest $request): RedirectResponse
    {
        $this->authorize('store', Post::class);


        $post = Post::create($request->validated());

        $request->session()->flash('post.id', $post->id);

        return redirect()->route('posts.index');
    }

    public function show(Request $request, Post $post): View
    {
        $this->authorize('show', $post);

        return view('post.show', compact('post'));
    }

    public function edit(Request $request, Post $post): View
    {
        $this->authorize('edit', $post);

        return view('post.edit', compact('post'));
    }

    public function update(PostUpdateRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);


        $post->update($request->validated());

        $request->session()->flash('post.id', $post->id);

        return redirect()->route('posts.index');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('destroy', $post);

        $post->delete();

        return redirect()->route('posts.index');
    }
}
