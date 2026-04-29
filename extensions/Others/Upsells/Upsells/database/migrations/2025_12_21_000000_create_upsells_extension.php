<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_upsells', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['upgrade', 'cross_sell', 'spend_minimum'])->default('upgrade');
            $table->string('template')->default('progress');
            $table->unsignedBigInteger('trigger_product_id')->nullable();
            $table->unsignedBigInteger('target_product_id');
            $table->decimal('minimum_spend', 10, 2)->nullable();
            $table->boolean('allow_multiple_claims')->default(false);
            $table->integer('max_claims')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('unlocked_title')->nullable();
            $table->text('unlocked_description')->nullable();
            $table->string('label')->nullable();
            $table->boolean('show_price_difference')->default(true);
            $table->boolean('auto_add_to_cart')->default(false);
            $table->json('preconfigured_options')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->foreign('trigger_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('target_product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('ext_upsells_settings', function (Blueprint $table) {
            $table->id();
            $table->string('template')->default('card');
            $table->boolean('use_custom')->default(false);
            $table->string('position')->default('below_product');
            $table->string('layout')->default('card');
            $table->string('primary_color')->nullable();
            $table->string('preview_background')->default('hsl(240, 18%, 9%)');
            $table->string('preview_card_background')->default('hsl(240, 13%, 11%)');
            $table->string('preview_text')->default('hsl(0, 0%, 100%)');
            $table->string('preview_muted')->default('hsl(220, 14%, 60%)');
            $table->string('preview_border')->default('hsl(0, 0%, 20%)');
            $table->string('background_color')->default('#eff6ff');
            $table->string('border_color')->default('#bfdbfe');
            $table->string('text_color')->default('#1e293b');
            $table->string('button_style')->default('primary');
            $table->integer('border_radius')->default(8);
            $table->integer('padding')->default(16);
            $table->integer('margin')->default(12);
            $table->boolean('show_label')->default(true);
            $table->boolean('show_description')->default(true);
            $table->boolean('show_icon')->default(false);
            $table->string('animation')->default('none');
            $table->timestamps();
        });

        DB::table('ext_upsells_settings')->insert([
            'template' => 'card',
            'use_custom' => false,
            'primary_color' => config('settings.primary_color', '#3b82f6'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                if (!Schema::hasColumn('cart_items', 'promotional_discount')) {
                    $table->decimal('promotional_discount', 10, 2)->nullable()->after('quantity');
                }
                if (!Schema::hasColumn('cart_items', 'promotional_type')) {
                    $table->string('promotional_type')->nullable()->after('promotional_discount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                if (Schema::hasColumn('cart_items', 'promotional_discount')) {
                    $table->dropColumn('promotional_discount');
                }
                if (Schema::hasColumn('cart_items', 'promotional_type')) {
                    $table->dropColumn('promotional_type');
                }
            });
        }

        Schema::dropIfExists('ext_upsells_settings');
        Schema::dropIfExists('ext_upsells');
    }
};

