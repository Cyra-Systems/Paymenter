<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_referral_commissions', function (Blueprint $table) {
            $table->foreignIdFor(Invoice::class)->nullable()->change();
            $table->foreignIdFor(InvoiceItem::class)->nullable()->change();
            $table->string('source_type', 32)->default(ReferralCommission::SOURCE_INVOICE)->after('award_signature');
            $table->string('source_label', 120)->nullable()->after('source_type');
            $table->foreignId('manual_schedule_id')->nullable()->after('source_label')->constrained('ext_referral_manual_commission_schedules')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->after('manual_schedule_id')->constrained('users')->nullOnDelete();
        });

        DB::table('ext_referral_commissions')
            ->whereNull('source_type')
            ->update([
                'source_type' => ReferralCommission::SOURCE_INVOICE,
            ]);
    }

    public function down(): void
    {
        Schema::table('ext_referral_commissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('manual_schedule_id');
            $table->dropColumn(['source_type', 'source_label']);
            $table->foreignIdFor(Invoice::class)->nullable(false)->change();
            $table->foreignIdFor(InvoiceItem::class)->nullable(false)->change();
        });
    }
};
