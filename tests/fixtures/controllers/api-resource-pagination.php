<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostStoreRequest;
use App\Http\Requests\PostUpdateRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\PostCollection
     */
    public function index(Request $request)
    {
        $posts = Post::paginate();

        return new PostCollection($posts);
    }

    /**
     * @param \App\Http\Requests\PostStoreRequest $request
     * @return \App\Http\Resources\PostResource
     */
    public function store(PostStoreRequest $request)
    {
        $post = Post::create($request->validated());

        return new PostResource($post);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Post $post
     * @return \App\Http\Resources\PostResource
     */
    public function show(Request $request, Post $post)
    {
        return new PostResource($post);
    }

    /**
     * @param \App\Http\Requests\PostUpdateRequest $request
     * @param \App\Models\Post $post
     * @return \App\Http\Resources\PostResource
     */
    public function update(PostUpdateRequest $request, Post $post)
    {
        $post->update($request->validated());

        return new PostResource($post);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Post $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Post $post)
    {
        $post->delete();

        return response()->noContent();
    }
}
