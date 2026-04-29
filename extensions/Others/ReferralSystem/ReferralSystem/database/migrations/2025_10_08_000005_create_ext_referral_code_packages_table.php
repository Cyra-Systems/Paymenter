<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_referral_code_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')->constrained('ext_referral_codes')->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            $table->decimal('revenue_share', 5, 2);
            $table->unsignedInteger('purchase_limit')->nullable();
            $table->unsignedInteger('purchases_count')->default(0);
            $table->timestamps();

            $table->unique(['referral_code_id', 'product_id'], 'ext_referral_code_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_referral_code_packages');
    }
};
