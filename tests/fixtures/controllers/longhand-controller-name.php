<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(UserStoreRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return redirect()->route('backend.users.show');
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        $request->session()->flash('user.id', $user->id);

        return redirect()->route('user.index');
    }
}
