<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostStoreRequest;
use App\Http\Requests\PostUpdateRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function index(Request $request): PostCollection
    {
        $posts = Post::paginate();

        return new PostCollection($posts);
    }

    public function store(PostStoreRequest $request): PostResource
    {
        $post = Post::create($request->validated());

        return new PostResource($post);
    }

    public function show(Request $request, Post $post): PostResource
    {
        return new PostResource($post);
    }

    public function update(PostUpdateRequest $request, Post $post): PostResource
    {
        $post->update($request->validated());

        return new PostResource($post);
    }

    public function destroy(Request $request, Post $post): Response
    {
        $post->delete();

        return response()->noContent();
    }
}
