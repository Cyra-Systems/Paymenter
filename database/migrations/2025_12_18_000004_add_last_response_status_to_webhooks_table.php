<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            // HTTP status of the last delivery attempt; 0 = network error; null = never attempted
            $table->unsignedSmallInteger('last_response_status')->nullable()->after('last_called_at');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn('last_response_status');
        });
    }
};
