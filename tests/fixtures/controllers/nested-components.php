<?php

namespace App\Http\Controllers\Admin;

use App\Events\NewUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Jobs\BuildAccount;
use App\Models\Admin\User;
use App\Notification\InviteNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::all();

        return view('admin.user.index', compact('users'));
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        Notification::send($user, new InviteNotification($user));

        BuildAccount::dispatch($user);

        event(new NewUser($user));

        $request->session()->flash('user.name', $user->name);

        return redirect()->route('admin.user.index');
    }
}
