<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_referral_manual_commission_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')
                ->constrained('ext_referral_codes', indexName: 'ref_manual_sched_code_fk')
                ->cascadeOnDelete();
            $table->foreignIdFor(Service::class)
                ->nullable()
                ->constrained(indexName: 'ref_manual_sched_service_fk')
                ->nullOnDelete();
            $table->foreignIdFor(User::class)
                ->nullable()
                ->constrained(indexName: 'ref_manual_sched_user_fk')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users', indexName: 'ref_manual_sched_creator_fk')
                ->nullOnDelete();
            $table->string('title', 120);
            $table->text('notes')->nullable();
            $table->string('currency_code', 3);
            $table->decimal('amount', 12, 2);
            $table->string('status', 32)->default('active');
            $table->string('frequency_unit', 16)->default('month');
            $table->unsignedInteger('frequency_interval')->default(1);
            $table->timestamp('starts_at');
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_cycles')->nullable();
            $table->unsignedInteger('cycles_generated')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_run_at'], 'ext_referral_manual_commission_schedules_status_next_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_referral_manual_commission_schedules');
    }
};
