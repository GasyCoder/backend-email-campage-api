<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingLink extends Model
{
    protected $fillable = ['message_id', 'hash', 'url'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
