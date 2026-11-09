<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->boolean('show_on_homepage')->default(false)->after('in_menu')->index();
        });

        Schema::table('articles', function (Blueprint $table): void {
            $table->string('source_name', 180)->nullable()->after('meta_description');
            $table->string('source_url', 2048)->nullable()->after('source_name');
        });

        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('request_type')->index();
            $table->string('name', 120);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('organization', 180)->nullable();
            $table->string('subject', 200);
            $table->text('message');
            $table->string('news_headline', 255)->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('source_attribution')->nullable();
            $table->string('campaign_budget', 120)->nullable();
            $table->date('campaign_start')->nullable();
            $table->date('campaign_end')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');

        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['source_name', 'source_url']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex(['show_on_homepage']);
            $table->dropColumn('show_on_homepage');
        });
    }
};
