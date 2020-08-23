<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagStoreRequest;
use App\Http\Requests\TagUpdateRequest;
use App\Http\Resources\TagCollection;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\TagCollection
     */
    public function index(Request $request)
    {
        $tags = Tag::all();

        return new TagCollection($tags);
    }

    /**
     * @param \App\Http\Requests\TagStoreRequest $request
     * @return \App\Http\Resources\TagResource
     */
    public function store(TagStoreRequest $request)
    {
        $tag = Tag::create($request->validated());

        return new TagResource($tag);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Tag $tag
     * @return \App\Http\Resources\TagResource
     */
    public function show(Request $request, Tag $tag)
    {
        return new TagResource($tag);
    }

    /**
     * @param \App\Http\Requests\TagUpdateRequest $request
     * @param \App\Models\Tag $tag
     * @return \App\Http\Resources\TagResource
     */
    public function update(TagUpdateRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return new TagResource($tag);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Tag $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Tag $tag)
    {
        $tag->delete();

        return response()->noContent();
    }
}
