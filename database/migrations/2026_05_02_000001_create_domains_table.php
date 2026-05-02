<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('sld')->nullable();
            $table->string('tld')->nullable();
            $table->string('type')->default('register');
            $table->string('provider')->default('enom');
            $table->string('status')->default('pending');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->nullableMorphs('bindable');
            $table->foreignId('parent_domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->string('forward_url')->nullable();
            $table->string('forward_type')->nullable();
            $table->unsignedSmallInteger('period')->default(1);
            $table->boolean('auto_renew')->default(false);
            $table->boolean('id_protect')->default(false);
            $table->boolean('locked')->default(true);
            $table->string('auth_code')->nullable();
            $table->json('nameservers')->nullable();
            $table->json('contacts')->nullable();
            $table->json('provider_meta')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['provider', 'status']);
            $table->index('expires_at');
        });

        Schema::create('domain_binding_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->nullableMorphs('previous_bindable');
            $table->nullableMorphs('new_bindable');
            $table->string('old_hostname')->nullable();
            $table->string('new_hostname')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('domain_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->boolean('encrypted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_settings');
        Schema::dropIfExists('domain_binding_history');
        Schema::dropIfExists('domains');
    }
};
