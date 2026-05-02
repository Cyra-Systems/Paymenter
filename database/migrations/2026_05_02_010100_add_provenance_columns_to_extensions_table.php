<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extensions', function (Blueprint $table) {
            $table->string('sha256', 64)->nullable()->after('type');
            $table->string('signature')->nullable()->after('sha256');
            $table->text('source_url')->nullable()->after('signature');
            $table->string('installed_version')->nullable()->after('source_url');
        });
    }

    public function down(): void
    {
        Schema::table('extensions', function (Blueprint $table) {
            $table->dropColumn(['sha256', 'signature', 'source_url', 'installed_version']);
        });
    }
};
