<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('facebook_post_status', 24)->default('not_configured');
            $table->string('facebook_post_id')->nullable();
            $table->timestamp('facebook_posted_at')->nullable();
            $table->text('facebook_post_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_post_status',
                'facebook_post_id',
                'facebook_posted_at',
                'facebook_post_error',
            ]);
        });
    }
};
