<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = ['user_id', 'name', 'email', 'category', 'message'];

    /** The categories a customer or owner can pick from. */
    public const CATEGORIES = [
        'general' => 'General',
        'booking' => 'A booking problem',
        'bug' => 'Something is broken',
        'owner' => 'I run a venue',
        'suggestion' => 'A suggestion',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
