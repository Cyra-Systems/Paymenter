<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')->constrained('ext_referral_codes')->cascadeOnDelete();
            $table->foreignId('withdrawal_id')->nullable()->constrained('ext_referral_withdrawals')->nullOnDelete();
            $table->foreignIdFor(Invoice::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(InvoiceItem::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Service::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 3);
            $table->string('status')->default(ReferralCommission::STATUS_AVAILABLE);
            $table->json('meta')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_referral_commissions');
    }
};
