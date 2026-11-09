<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = [
        'request_type',
        'name',
        'email',
        'phone',
        'organization',
        'subject',
        'message',
        'news_headline',
        'category_id',
        'source_attribution',
        'campaign_budget',
        'campaign_start',
        'campaign_end',
        'attachment_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'campaign_start' => 'date',
            'campaign_end' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
