<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_profiles', function (Blueprint $table): void {
            $table->foreignId('onboarded_by_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(['portfolio_id', 'onboarded_by_user_id']);
        });

        Schema::table('asset_stakeholders', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'relationship_type', 'ends_on', 'asset_id'],
                'asset_stakeholders_manager_scope_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('asset_stakeholders', function (Blueprint $table): void {
            $table->dropIndex('asset_stakeholders_manager_scope_index');
        });

        Schema::table('tenant_profiles', function (Blueprint $table): void {
            $table->dropForeign(['onboarded_by_user_id']);
            $table->dropIndex(['portfolio_id', 'onboarded_by_user_id']);
            $table->dropColumn('onboarded_by_user_id');
        });
    }
};
