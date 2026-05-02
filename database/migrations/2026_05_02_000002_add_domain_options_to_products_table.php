<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('domain_enabled')->default(false)->after('enabled');
            $table->json('domain_options')->nullable()->after('domain_enabled');
            $table->foreignId('domain_parent_id')->nullable()->after('domain_options')->constrained('domains')->nullOnDelete();
            $table->boolean('domain_required')->default(false)->after('domain_parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('domain_parent_id');
            $table->dropColumn(['domain_enabled', 'domain_options', 'domain_required']);
        });
    }
};
