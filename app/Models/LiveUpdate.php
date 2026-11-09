<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveUpdate extends Model
{
    protected $fillable = ['live_event_id', 'user_id', 'body'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(LiveEvent::class, 'live_event_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
