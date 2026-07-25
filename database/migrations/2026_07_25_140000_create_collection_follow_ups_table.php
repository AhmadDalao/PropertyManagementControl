<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_installment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contact_method');
            $table->string('outcome');
            $table->timestamp('contacted_at');
            $table->decimal('promised_amount', 14, 2)->nullable();
            $table->date('promised_on')->nullable();
            $table->date('next_follow_up_on');
            $table->text('note');
            $table->timestamps();

            $table->index(
                ['portfolio_id', 'next_follow_up_on'],
                'collection_follow_ups_portfolio_queue',
            );
            $table->index(
                ['lease_installment_id', 'id'],
                'collection_follow_ups_installment_history',
            );
            $table->index(
                ['assigned_to_user_id', 'next_follow_up_on'],
                'collection_follow_ups_assignee_queue',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_follow_ups');
    }
};
