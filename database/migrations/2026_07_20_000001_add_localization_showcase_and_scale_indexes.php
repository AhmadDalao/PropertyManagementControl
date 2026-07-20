<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showcase_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('status')->default('queued')->index();
            $table->unsignedInteger('target_properties')->default(40);
            $table->unsignedInteger('generated_properties')->default(0);
            $table->json('counts_json')->nullable();
            $table->text('failure_details')->nullable();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
        });

        Schema::table('portfolios', function (Blueprint $table) {
            $table->text('address_ar')->nullable()->after('address');
            $table->foreignId('showcase_dataset_id')
                ->nullable()
                ->after('id')
                ->constrained('showcase_datasets')
                ->nullOnDelete();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->text('address_ar')->nullable()->after('address');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('showcase_dataset_id')
                ->nullable()
                ->after('id')
                ->constrained('showcase_datasets')
                ->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['portfolio_id', 'status', 'created_at'], 'users_portfolio_status_created_idx');
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->index(['portfolio_id', 'status', 'asset_type', 'created_at'], 'assets_portfolio_status_type_created_idx');
            $table->index(['portfolio_id', 'occupancy_status', 'asset_type'], 'assets_portfolio_occupancy_type_idx');
        });
        Schema::table('tenant_profiles', function (Blueprint $table) {
            $table->index(['portfolio_id', 'status', 'created_at'], 'tenants_portfolio_status_created_idx');
        });
        Schema::table('leases', function (Blueprint $table) {
            $table->index(['portfolio_id', 'status', 'ends_at'], 'leases_portfolio_status_ends_idx');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['portfolio_id', 'status', 'received_on'], 'payments_portfolio_status_received_idx');
            $table->index(['portfolio_id', 'type', 'received_on'], 'payments_portfolio_type_received_idx');
        });
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->index(['portfolio_id', 'status', 'priority', 'requested_at'], 'maintenance_portfolio_status_priority_idx');
            $table->index(['portfolio_id', 'asset_id', 'status'], 'maintenance_portfolio_asset_status_idx');
        });
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->index(['portfolio_id', 'status', 'incurred_on'], 'expenses_portfolio_status_incurred_idx');
            $table->index(['portfolio_id', 'category', 'incurred_on'], 'expenses_portfolio_category_incurred_idx');
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->index(['portfolio_id', 'type', 'created_at'], 'documents_portfolio_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_portfolio_type_created_idx');
        });
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->dropIndex('expenses_portfolio_status_incurred_idx');
            $table->dropIndex('expenses_portfolio_category_incurred_idx');
        });
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex('maintenance_portfolio_status_priority_idx');
            $table->dropIndex('maintenance_portfolio_asset_status_idx');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_portfolio_status_received_idx');
            $table->dropIndex('payments_portfolio_type_received_idx');
        });
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex('leases_portfolio_status_ends_idx');
        });
        Schema::table('tenant_profiles', function (Blueprint $table) {
            $table->dropIndex('tenants_portfolio_status_created_idx');
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('assets_portfolio_status_type_created_idx');
            $table->dropIndex('assets_portfolio_occupancy_type_idx');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_portfolio_status_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('showcase_dataset_id');
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('address_ar');
        });
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('showcase_dataset_id');
            $table->dropColumn('address_ar');
        });

        Schema::dropIfExists('showcase_datasets');
    }
};
