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
        Schema::create('ext_page_builder_pages', function (Blueprint $table) {
            $table->id();
            $table->string('route')->unique();
            $table->boolean('show_in_nav')->default(false);
            $table->boolean('show_in_dropdown')->default(false);
            $table->string('dropdown_name')->nullable();
            $table->integer('nav_priority')->default(0);
            $table->string('title')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('meta_og_title')->nullable();
            $table->text('meta_og_description')->nullable();
            $table->string('meta_og_image')->nullable();
            $table->string('meta_twitter_title')->nullable();
            $table->text('meta_twitter_description')->nullable();
            $table->string('meta_twitter_image')->nullable();
            $table->string('meta_twitter_card')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('sections')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_page_builder_pages');
    }
};
