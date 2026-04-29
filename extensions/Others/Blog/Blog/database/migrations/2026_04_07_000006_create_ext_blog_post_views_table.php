<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ext_blog_posts') || Schema::hasTable('ext_blog_post_views')) {
            return;
        }

        Schema::create('ext_blog_post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('ext_blog_posts')->cascadeOnDelete();
            $table->string('session_id', 191)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['blog_post_id', 'created_at']);
            $table->index(['blog_post_id', 'session_id']);
            $table->index(['blog_post_id', 'ip_address']);
            $table->index(['blog_post_id', 'session_id', 'ip_address'], 'ext_blog_post_views_post_session_ip_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_blog_post_views');
    }
};
