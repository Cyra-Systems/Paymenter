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
            if (!Schema::hasColumn('ext_blog_posts', 'seo_title')) {
                $table->string('seo_title', 255)->nullable()->after('cover_image_url');
            }

            if (!Schema::hasColumn('ext_blog_posts', 'seo_description')) {
                $table->string('seo_description', 320)->nullable()->after('seo_title');
            }

            if (!Schema::hasColumn('ext_blog_posts', 'seo_keywords')) {
                $table->string('seo_keywords', 500)->nullable()->after('seo_description');
            }

            if (!Schema::hasColumn('ext_blog_posts', 'seo_canonical_url')) {
                $table->string('seo_canonical_url', 512)->nullable()->after('seo_keywords');
            }

            if (!Schema::hasColumn('ext_blog_posts', 'seo_image_url')) {
                $table->string('seo_image_url', 512)->nullable()->after('seo_canonical_url');
            }

            if (!Schema::hasColumn('ext_blog_posts', 'seo_robots')) {
                $table->string('seo_robots', 40)->nullable()->after('seo_image_url');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ext_blog_posts')) {
            return;
        }

        Schema::table('ext_blog_posts', function (Blueprint $table) {
            foreach (['seo_robots', 'seo_image_url', 'seo_canonical_url', 'seo_keywords', 'seo_description', 'seo_title'] as $column) {
                if (Schema::hasColumn('ext_blog_posts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
