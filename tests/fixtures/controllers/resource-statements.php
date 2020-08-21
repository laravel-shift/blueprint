<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\UserCollection
     */
    public function index(Request $request)
    {
        return new UserCollection($users->paginate());
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\UserResource
     */
    public function store(Request $request)
    {
        return new UserResource($user);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @return \App\Http\Resources\UserResource
     */
    public function show(Request $request, User $user)
    {
        return new UserResource($user);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\UserCollection
     */
    public function all(Request $request)
    {
        return new UserCollection($users);
    }
}
