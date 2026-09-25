<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
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

    public function assign(Request $request): View
    {
        $user = User::find($user_id);

        return view('post.assign', [
            'user' => $user,
        ]);
    }
}
