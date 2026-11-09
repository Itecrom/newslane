<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveEvent extends Model
{
    protected $fillable = ['title', 'slug', 'summary', 'status', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function updates(): HasMany
    {
        return $this->hasMany(LiveUpdate::class);
    }
}
