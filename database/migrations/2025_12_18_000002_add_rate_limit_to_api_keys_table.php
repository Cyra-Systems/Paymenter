<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            // null = unlimited; integer = max requests per minute
            $table->unsignedSmallInteger('rate_limit')->nullable()->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('rate_limit');
        });
    }
};
