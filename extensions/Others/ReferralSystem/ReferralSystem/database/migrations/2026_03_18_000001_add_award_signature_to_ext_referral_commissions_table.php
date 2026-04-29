<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_referral_commissions', function (Blueprint $table) {
            $table->string('award_signature', 64)->nullable()->after('user_id');
        });

        DB::table('ext_referral_commissions')
            ->select('invoice_item_id', 'referral_code_id', DB::raw('MIN(id) as root_id'))
            ->groupBy('invoice_item_id', 'referral_code_id')
            ->orderBy('root_id')
            ->chunk(500, function ($rows): void {
                foreach ($rows as $row) {
                    if (!$row->invoice_item_id || !$row->referral_code_id) {
                        continue;
                    }

                    DB::table('ext_referral_commissions')
                        ->where('id', $row->root_id)
                        ->update([
                            'award_signature' => $row->invoice_item_id . ':' . $row->referral_code_id,
                        ]);
                }
            });

        Schema::table('ext_referral_commissions', function (Blueprint $table) {
            $table->unique('award_signature', 'ext_referral_commissions_award_signature_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ext_referral_commissions', function (Blueprint $table) {
            $table->dropUnique('ext_referral_commissions_award_signature_unique');
            $table->dropColumn('award_signature');
        });
    }
};
