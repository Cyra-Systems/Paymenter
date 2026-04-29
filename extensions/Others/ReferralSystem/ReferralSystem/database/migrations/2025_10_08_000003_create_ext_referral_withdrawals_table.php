<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralWithdrawal;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_referral_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')->constrained('ext_referral_codes')->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 3);
            $table->string('status')->default(ReferralWithdrawal::STATUS_PENDING);
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignIdFor(User::class, 'processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_referral_withdrawals');
    }
};
