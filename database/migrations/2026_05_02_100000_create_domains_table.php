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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('sld', 63);
            $table->string('tld', 63);
            $table->string('fqdn', 253)->unique();
            $table->string('registrar')->default('enom');
            $table->string('status')->default('pending');
            $table->text('auth_code')->nullable();
            $table->boolean('locked')->default(false);
            $table->boolean('auto_renew')->default(true);
            $table->boolean('id_protect')->default(false);
            $table->dateTime('registered_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->foreignIdFor(Service::class, 'registered_via_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->json('registrar_data')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('price', 17, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
