<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Manage Owners')]
class Owners extends Component
{
    public function toggleSuspend(int $userId): void
    {
        $owner = User::where('role', UserRole::Owner->value)->findOrFail($userId);

        $owner->update(['is_suspended' => ! $owner->is_suspended]);
    }

    public function render()
    {
        return view('livewire.admin.owners', [
            'owners' => User::where('role', UserRole::Owner->value)
                ->withCount('venues')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
