<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('requires_domain')->default(false)->after('hidden');
            $table->json('allowed_domain_paths')->nullable()->after('requires_domain');
            $table->boolean('subdomain_only')->default(false)->after('allowed_domain_paths');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['requires_domain', 'allowed_domain_paths', 'subdomain_only']);
        });
    }
};
