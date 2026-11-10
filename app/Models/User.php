<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'bio'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    protected $attributes = ['is_active' => true];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canManageNewsroom(): bool
    {
        return in_array($this->role, ['admin', 'editor'], true);
    }

    public function canPublishArticles(): bool
    {
        return $this->canManageNewsroom();
    }

    public function canEditArticle(Article $article): bool
    {
        return $this->canManageNewsroom()
            || (in_array($this->role, ['reporter', 'intern'], true)
                && $article->user_id === $this->id
                && $article->status === 'draft');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
