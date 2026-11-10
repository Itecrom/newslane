<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = [
        'category_id',
        'user_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'image_alt',
        'image_caption',
        'image_credit',
        'image_credit_url',
        'status',
        'meta_description',
        'source_name',
        'source_url',
        'video_embed_url',
        'featured',
        'breaking',
        'include_in_africa',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'breaking' => 'boolean',
            'include_in_africa' => 'boolean',
            'published_at' => 'datetime',
            'facebook_posted_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ArticleLike::class);
    }
}
