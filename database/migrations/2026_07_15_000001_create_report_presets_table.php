<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('resource')->index();
            $table->string('title_en');
            $table->string('title_ar')->nullable();
            $table->json('filters_json')->nullable();
            $table->string('visibility')->default('private')->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->index(['portfolio_id', 'resource', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_presets');
    }
};
