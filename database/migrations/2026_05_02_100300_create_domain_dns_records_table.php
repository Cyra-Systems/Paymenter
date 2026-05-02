<?php

use App\Models\Domain;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Domain::class)->constrained()->cascadeOnDelete();
            $table->string('hostname');
            $table->string('type', 10);
            $table->text('value');
            $table->integer('priority')->nullable();
            $table->integer('ttl')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'hostname', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_dns_records');
    }
};
