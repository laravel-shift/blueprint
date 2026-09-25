<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostUpdateRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function publish(Request $request, Post $post): RedirectResponse
    {
        $post->update($request->only('published_at'));

        return redirect()->route('posts.index');
    }

    public function update(PostUpdateRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->safe()->only('title', 'content'));

        return redirect()->route('posts.index');
    }
}
