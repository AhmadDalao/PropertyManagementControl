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
        Schema::create('lease_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('line_type')->default('rent')->index();
            $table->string('label');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date')->index();
            $table->decimal('amount_due', 14, 2);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->string('status')->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lease_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_installments');
    }
};
