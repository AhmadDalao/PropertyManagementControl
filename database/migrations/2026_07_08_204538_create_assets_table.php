<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('assets')->cascadeOnDelete();
            $table->string('asset_type')->index();
            $table->string('usage_type')->index();
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('code')->unique();
            $table->string('slug')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->string('occupancy_status')->default('vacant')->index();
            $table->boolean('rentable')->default(false)->index();
            $table->decimal('valuation_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->decimal('area', 12, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('level_label')->nullable();
            $table->string('unit_label')->nullable();
            $table->text('address')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
