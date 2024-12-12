<?php

namespace App\Livewire;

use App\Events\ProfileUpdated;
use App\Http\Resources\UserResource;
use App\Jobs\UpdateProfile;
use App\Mail\ReviewProfile;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UpdateProfile extends Component
{
    #[Locked]
    public $user;

    public function mount($user): void
    {
        $this->user = $user;
    }

    public function render(): View
    {
        return view('livewire.update-profile');
    }

    public function update()
    {
        UpdateProfile::dispatch($this->user);

        ProfileUpdated::dispatch($this->user);

        session()->flash('user.name', $this->user->name);

        $this->user->notify(new ReviewProfile($this->user));

        return redirect()->route('user.show', [$this->user]);

        return view('user.show', [
            'user' => $this->user,
            'extra' => $extra,
        ]);

        return new UserResource($this->user);

        return $this->user;

        Mail::to($this->user->email)->send(new ReviewProfile($this->user));

        session()->store('user.id', $this->user->id);
    }
}
