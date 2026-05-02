<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_tlds', function (Blueprint $table) {
            $table->id();
            $table->string('tld')->unique();
            $table->boolean('enabled')->default(true);
            $table->decimal('register_price', 17, 2)->default(0);
            $table->decimal('transfer_price', 17, 2)->default(0);
            $table->decimal('renewal_price', 17, 2)->default(0);
            $table->decimal('redemption_price', 17, 2)->default(0);
            $table->string('currency_code', 3);
            $table->decimal('margin_percent', 8, 2)->default(0);
            $table->unsignedTinyInteger('min_years')->default(1);
            $table->unsignedTinyInteger('max_years')->default(10);
            $table->boolean('whois_privacy_supported')->default(true);
            $table->boolean('transfer_supported')->default(true);
            $table->boolean('epp_required')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_tlds');
    }
};
