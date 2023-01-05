<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $users = User::all();

        return view('user.index', compact('users'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request): \Illuminate\View\View
    {
        return view('user.create');
    }

    /**
     * @param \App\Http\Requests\UserStoreRequest $request
     * @return \Illuminate\Routing\Redirector
     */
    public function store(UserStoreRequest $request): \Illuminate\Routing\Redirector
    {
        $user = User::create($request->validated());

        $request->session()->flash('user.id', $user->id);

        return redirect()->route('user.index');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return \Illuminate\View\View
     */
    public function show(Request $request, User $user): \Illuminate\View\View
    {
        return view('user.show', compact('user'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return \Illuminate\View\View
     */
    public function edit(Request $request, User $user): \Illuminate\View\View
    {
        return view('user.edit', compact('user'));
    }

    /**
     * @param \App\Http\Requests\UserUpdateRequest $request
     * @param \App\Models\User $user
     * @return \Illuminate\Routing\Redirector
     */
    public function update(UserUpdateRequest $request, User $user): \Illuminate\Routing\Redirector
    {
        $user->update($request->validated());

        $request->session()->flash('user.id', $user->id);

        return redirect()->route('user.index');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return \Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, User $user): \Illuminate\Routing\Redirector
    {
        $user->delete();

        return redirect()->route('user.index');
    }
}
