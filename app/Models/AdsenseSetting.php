<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdsenseSetting extends Model
{
    protected $fillable = ['enabled'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }
}
