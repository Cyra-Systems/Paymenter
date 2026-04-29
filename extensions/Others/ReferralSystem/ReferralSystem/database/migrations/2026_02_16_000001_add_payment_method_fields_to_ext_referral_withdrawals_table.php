<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_referral_withdrawals', function (Blueprint $table) {
            $table->string('payment_method', 64)->nullable()->after('currency_code')->index();
            $table->text('payment_method_info')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('ext_referral_withdrawals', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropColumn(['payment_method', 'payment_method_info']);
        });
    }
};
