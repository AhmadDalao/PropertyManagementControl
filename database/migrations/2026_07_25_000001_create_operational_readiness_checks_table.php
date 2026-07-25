<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_readiness_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope_key', 80);
            $table->string('key', 80);
            $table->boolean('is_confirmed')->default(false);
            $table->text('evidence')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['scope_key', 'key']);
            $table->index(['portfolio_id', 'is_confirmed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_readiness_checks');
    }
};
