<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 16);
            $table->string('version')->nullable();
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->longText('icon')->nullable();
            $table->text('download_url');
            $table->string('sha256', 64);
            $table->string('signature')->nullable();
            $table->boolean('has_migrations')->default(false);
            $table->timestamp('synced_at');
            $table->json('raw_meta')->nullable();
            $table->timestamps();

            $table->unique(['name', 'type', 'version']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
