<?php

namespace App\Http\Controllers\Admin;

use App\Admin\User;
use App\Events\NewUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Jobs\BuildAccount;
use App\Notification\InviteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class UserController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = User::all();

        return view('admin.user.index', compact('users'));
    }

    /**
     * @param \App\Http\Requests\Admin\UserStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserStoreRequest $request)
    {
        $user = User::create($request->validated());

        Notification::send($user, new InviteNotification($user));

        BuildAccount::dispatch($user);

        event(new NewUser($user));

        $request->session()->flash('user.name', $user->name);

        return redirect()->route('admin.user.index');
    }
}
