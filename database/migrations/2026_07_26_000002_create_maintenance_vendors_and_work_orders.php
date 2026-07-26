<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_vendors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('service_category', 40);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['portfolio_id', 'status']);
            $table->index(['portfolio_id', 'service_category']);
        });

        Schema::create('maintenance_work_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_request_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('maintenance_vendors')
                ->nullOnDelete();
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('reference_code')->unique();
            $table->string('vendor_name');
            $table->string('vendor_phone', 40)->nullable();
            $table->string('status', 20)->default('draft');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->decimal('estimated_amount', 14, 2)->nullable();
            $table->decimal('final_amount', 14, 2)->nullable();
            $table->char('currency', 3)->default('SAR');
            $table->text('scope');
            $table->text('completion_notes')->nullable();
            $table->boolean('tenant_access_required')->default(false);
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['portfolio_id', 'status']);
            $table->index(['maintenance_request_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_work_orders');
        Schema::dropIfExists('maintenance_vendors');
    }
};
