<?php

namespace Some\App\Other\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Some\App\Http\Requests\UserStoreRequest;
use Some\App\User;

class UserController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = User::all();

        return view('user.index', compact('users'));
    }

    /**
     * @param \Some\App\Http\Requests\UserStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserStoreRequest $request)
    {
        $user = User::create($request->validated());

        $request->session()->flash('user.name', $user->name);

        return redirect()->route('post.index');
    }
}
