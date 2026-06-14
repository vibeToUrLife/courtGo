<?php

namespace App\Livewire\Owner;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Billing & Payouts')]
class Billing extends Component
{
    #[Validate('nullable|string|max:255')]
    public string $business_registration_number = '';

    public function mount(): void
    {
        $this->business_registration_number = auth()->user()->business_registration_number ?? '';
    }

    public function saveBrn(): void
    {
        $validated = $this->validate();

        auth()->user()->update($validated);

        session()->flash('brn_saved', true);
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.owner.billing', [
            'subscribed' => $user->subscribed('default'),
            'onboarded' => (bool) $user->connect_onboarded,
            'canAcceptBookings' => $user->canAcceptBookings(),
        ]);
    }
}
