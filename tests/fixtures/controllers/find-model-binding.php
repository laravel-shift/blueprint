<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function show(Request $request, Post $post): View
    {
        return view('post.show', [
            'post' => $post,
        ]);
    }

    public function publish(Request $request, Post $post): RedirectResponse
    {
        $post->save();

        return redirect()->route('posts.show', ['post' => $post]);
    }

    public function archive(Request $request, Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('posts.index');
    }
}
