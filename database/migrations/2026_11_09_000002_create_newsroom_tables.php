<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description', 500)->nullable();
            $table->boolean('in_menu')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 320);
            $table->longText('body');
            $table->string('cover_image')->nullable();
            $table->string('image_alt', 180)->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('meta_description', 160)->nullable();
            $table->boolean('featured')->default(false)->index();
            $table->boolean('breaking')->default(false)->index();
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('email');
            $table->text('body');
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('article_likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->char('visitor_hash', 64);
            $table->timestamps();
            $table->unique(['article_id', 'visitor_hash']);
        });

        Schema::create('subscribers', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->char('verification_token_hash', 64)->nullable();
            $table->char('unsubscribe_token_hash', 64)->unique();
            $table->timestamp('verified_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('advertisements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('image_path');
            $table->string('target_url', 2048);
            $table->string('placement')->default('homepage');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('live_events', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 500);
            $table->string('status')->default('live')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('live_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('live_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['live_event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_updates');
        Schema::dropIfExists('live_events');
        Schema::dropIfExists('advertisements');
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('article_likes');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('categories');
    }
};
