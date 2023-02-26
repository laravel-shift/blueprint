<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagStoreRequest;
use App\Http\Requests\TagUpdateRequest;
use App\Http\Resources\TagCollection;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagController extends Controller
{
    public function index(Request $request): TagCollection
    {
        $tags = Tag::all();

        return new TagCollection($tags);
    }

    public function store(TagStoreRequest $request): TagResource
    {
        $tag = Tag::create($request->validated());

        return new TagResource($tag);
    }

    public function show(Request $request, Tag $tag): TagResource
    {
        return new TagResource($tag);
    }

    public function update(TagUpdateRequest $request, Tag $tag): TagResource
    {
        $tag->update($request->validated());

        return new TagResource($tag);
    }

    public function destroy(Request $request, Tag $tag): Response
    {
        $tag->delete();

        return response()->noContent();
    }
}
