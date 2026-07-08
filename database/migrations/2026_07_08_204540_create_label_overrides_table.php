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
        Schema::create('label_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->nullable()->constrained()->nullOnDelete();
            $table->string('group_name')->index();
            $table->string('override_key')->index();
            $table->string('locale', 5)->index();
            $table->text('value');
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->timestamps();

            $table->unique(['portfolio_id', 'group_name', 'override_key', 'locale'], 'label_overrides_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_overrides');
    }
};
