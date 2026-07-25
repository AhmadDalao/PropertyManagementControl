<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_move_outs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('planned');
            $table->date('move_out_date');
            $table->string('reason');
            $table->string('deposit_disposition')->default('pending');
            $table->decimal('deposit_deduction_amount', 14, 2)->default(0);
            $table->boolean('keys_returned')->default(false);
            $table->decimal('balance_at_completion', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['portfolio_id', 'status', 'move_out_date'], 'lease_move_outs_portfolio_queue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_move_outs');
    }
};
