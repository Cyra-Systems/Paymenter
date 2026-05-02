<?php

use App\Models\Domain;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_service_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Domain::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Service::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('hostname', 253);
            $table->unsignedBigInteger('npm_proxy_host_id')->nullable();
            $table->unsignedBigInteger('npm_certificate_id')->nullable();
            $table->unsignedBigInteger('npm_redirection_host_id')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('transitioning')->default(false);
            $table->string('forward_target', 253)->nullable();
            $table->dateTime('bound_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'status']);
            $table->index(['domain_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_service_bindings');
    }
};
