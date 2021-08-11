<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostStoreRequest;
use App\Http\Requests\PostUpdateRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\PostCollection
     */
    public function index(Request $request): \App\Http\Resources\PostCollection
    {
        $posts = Post::paginate();

        return new PostCollection($posts);
    }

    /**
     * @param \App\Http\Requests\PostStoreRequest $request
     * @return \App\Http\Resources\PostResource
     */
    public function store(PostStoreRequest $request): \App\Http\Resources\PostResource
    {
        $post = Post::create($request->validated());

        return new PostResource($post);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Post $post
     * @return \App\Http\Resources\PostResource
     */
    public function show(Request $request, Post $post): \App\Http\Resources\PostResource
    {
        return new PostResource($post);
    }

    /**
     * @param \App\Http\Requests\PostUpdateRequest $request
     * @param \App\Post $post
     * @return \App\Http\Resources\PostResource
     */
    public function update(PostUpdateRequest $request, Post $post): \App\Http\Resources\PostResource
    {
        $post->update($request->validated());

        return new PostResource($post);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Post $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Post $post): \Illuminate\Http\Response
    {
        $post->delete();

        return response()->noContent();
    }
}
