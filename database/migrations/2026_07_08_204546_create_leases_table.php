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
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('managed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('leaseable_type');
            $table->unsignedBigInteger('leaseable_id');
            $table->string('code')->unique();
            $table->string('status')->default('draft')->index();
            $table->string('payment_frequency')->default('monthly');
            $table->date('started_at');
            $table->date('ends_at');
            $table->date('signed_at')->nullable();
            $table->unsignedInteger('renewal_notice_days')->default(30);
            $table->decimal('rent_amount', 14, 2);
            $table->decimal('deposit_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->text('notes')->nullable();
            $table->json('terms_json')->nullable();
            $table->timestamps();

            $table->index(['leaseable_type', 'leaseable_id']);
            $table->index(['portfolio_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
