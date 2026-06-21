<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.customer', ['mainClass' => 'mx-auto max-w-3xl px-6 py-10'])]
#[Title('Help center')]
class Help extends Component
{
    public function render()
    {
        return view('livewire.help');
    }
}
