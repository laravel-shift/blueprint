<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $posts = Post::where('title', $title)->where('content', $content)->orderBy('published_at')->limit(5)->get();

        return view('post.index', compact('posts'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Post $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Post $post)
    {
        $post = Post::find($id);

        return view('post.edit', compact('post'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Post $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        $post = Post::find($post->id);

        $post_ids = Post::where('title', $post->title)->take(3)->pluck('id');

        $post->save();

        return redirect()->route('post.edit', $post->id);
    }
}
