<?php

namespace App\Livewire;

use App\Events\ProfileUpdated;
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
        ProfileUpdated::dispatch($this->user);
    }
}
