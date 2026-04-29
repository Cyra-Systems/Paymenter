<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ext_blog_posts')) {
            return;
        }

        Schema::table('ext_blog_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('ext_blog_posts', 'views')) {
                $table->unsignedBigInteger('views')->default(0)->after('tags');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ext_blog_posts')) {
            return;
        }

        Schema::table('ext_blog_posts', function (Blueprint $table) {
            if (Schema::hasColumn('ext_blog_posts', 'views')) {
                $table->dropColumn('views');
            }
        });
    }
};
