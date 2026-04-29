<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ext_blog_posts') || Schema::hasTable('ext_blog_post_feedback')) {
            return;
        }

        Schema::create('ext_blog_post_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('ext_blog_posts')->cascadeOnDelete();
            $table->boolean('is_helpful');
            $table->json('reasons')->nullable();
            $table->text('note')->nullable();
            $table->string('session_id', 191);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->unique(['blog_post_id', 'session_id']);
            $table->index(['blog_post_id', 'is_helpful']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_blog_post_feedback');
    }
};
