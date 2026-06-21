<?php

namespace App\Livewire;

use App\Models\Feedback as FeedbackModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.customer', ['mainClass' => 'mx-auto max-w-2xl px-6 py-10'])]
#[Title('Send feedback')]
class Feedback extends Component
{
    public string $category = 'general';

    public string $email = '';

    public string $message = '';

    public bool $sent = false;

    public function mount(): void
    {
        $this->email = auth()->user()?->email ?? '';
    }

    public function submit(): void
    {
        $data = $this->validate([
            'category' => ['required', Rule::in(array_keys(FeedbackModel::CATEGORIES))],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $feedback = FeedbackModel::create([
            'user_id' => auth()->id(),
            'name' => auth()->user()?->name,
            'email' => $data['email'],
            'category' => $data['category'],
            'message' => $data['message'],
        ]);

        // Also email the team — but never let a mail hiccup lose the stored feedback.
        rescue(fn () => Mail::raw(
            "Category: {$feedback->category}\nFrom: {$feedback->email}".(auth()->id() ? ' (user #'.auth()->id().')' : '')."\n\n{$feedback->message}",
            fn ($m) => $m->to(config('courtgo.support_email'))->subject('CourtGo feedback: '.FeedbackModel::CATEGORIES[$feedback->category])
        ), report: true);

        $this->reset('message');
        $this->sent = true;
    }

    public function sendAnother(): void
    {
        $this->sent = false;
    }

    public function render()
    {
        return view('livewire.feedback', ['categories' => FeedbackModel::CATEGORIES]);
    }
}
