<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ext_blog_post_feedback')) {
            return;
        }

        Schema::table('ext_blog_post_feedback', function (Blueprint $table) {
            if (!Schema::hasColumn('ext_blog_post_feedback', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('blog_post_id')->constrained('users')->nullOnDelete();
                $table->unique(['blog_post_id', 'user_id']);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ext_blog_post_feedback')) {
            return;
        }

        Schema::table('ext_blog_post_feedback', function (Blueprint $table) {
            if (Schema::hasColumn('ext_blog_post_feedback', 'user_id')) {
                $table->dropUnique('ext_blog_post_feedback_blog_post_id_user_id_unique');
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
