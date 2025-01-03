<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentStoreRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Http\Resources\CommentCollection;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    public function index(Request $request, Post $post): CommentCollection
    {
        $comments = $post->comments()->get();

        return new CommentCollection($comments);
    }

    public function store(CommentStoreRequest $request, Post $post): CommentResource
    {
        $comment = $post->comments()->create($request->validated());

        return new CommentResource($comment);
    }

    public function show(Request $request, Post $post, Comment $comment): CommentResource
    {
        return new CommentResource($comment);
    }

    public function update(CommentUpdateRequest $request, Post $post, Comment $comment): CommentResource
    {
        $comment->update($request->validated());

        return new CommentResource($comment);
    }

    public function destroy(Request $request, Post $post, Comment $comment): Response
    {
        $comment->delete();

        return response()->noContent();
    }
}
