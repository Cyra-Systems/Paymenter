<?php

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ext_referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Coupon::class)->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('status')->default(ReferralCode::STATUS_ACTIVE);
            $table->decimal('default_revenue_share', 5, 2)->default(0);
            $table->unsignedInteger('purchase_limit')->nullable();
            $table->unsignedInteger('purchases_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamp('suspended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_referral_codes');
    }
};
