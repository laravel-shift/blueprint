<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostCollection;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): PostCollection
    {
        $posts = Post::orderBy('published_at')->paginate();

        return new PostCollection($posts);
    }

    public function search(Request $request): PostCollection
    {
        $posts = Post::where('title', $title)->paginate();

        return new PostCollection($posts);
    }

    public function archive(Request $request): PostCollection
    {
        $posts = Post::where('title', $title)->orderBy('published_at')->paginate();

        return new PostCollection($posts);
    }

    public function latest(Request $request): PostCollection
    {
        $posts = Post::orderBy('published_at')->get();

        return new PostCollection($posts);
    }
}
