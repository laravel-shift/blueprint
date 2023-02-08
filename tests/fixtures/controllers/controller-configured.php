<?php

namespace Some\App\Other\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Some\App\Http\Requests\UserStoreRequest;
use Some\App\User;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::all();

        return view('user.index', compact('users'));
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        $request->session()->flash('user.name', $user->name);

        return redirect()->route('post.index');
    }
}
