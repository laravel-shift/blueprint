<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): UserCollection
    {
        $users = User::paginate();

        return new UserCollection($users);
    }

    public function store(Request $request): UserResource
    {
        return new UserResource($user);
    }

    public function show(Request $request, User $user): UserResource
    {
        return new UserResource($user);
    }

    public function all(Request $request): UserCollection
    {
        return new UserCollection($users);
    }
}
