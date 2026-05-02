<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('version')->nullable();
            $table->string('author')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->string('sha256', 64)->nullable();
            $table->string('signature')->nullable();
            $table->text('source_url')->nullable();
            $table->string('installed_version')->nullable();
            $table->timestamp('last_built_at')->nullable();
            $table->string('last_build_status', 16)->nullable();
            $table->string('last_build_log_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
