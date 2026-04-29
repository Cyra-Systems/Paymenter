<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralApplication;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_referral_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignId('referral_code_id')->nullable()->constrained('ext_referral_codes')->nullOnDelete();
            $table->string('status')->default(ReferralApplication::STATUS_PENDING);
            $table->string('requested_code')->nullable();
            $table->text('message')->nullable();
            $table->text('admin_notes')->nullable();
            $table->decimal('desired_revenue_share', 5, 2)->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_referral_applications');
    }
};
