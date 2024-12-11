<?php

namespace App\Livewire;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Livewire\Component;

class SimpleComponent extends Component
{
    public function render(): View
    {
        return view('livewire.simple-component');
    }

    public function action(): RedirectResponse
    {
        return redirect()->route('simple.page');
    }
}
