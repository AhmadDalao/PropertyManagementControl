<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->text('resolution_summary')->nullable()->after('resolved_at');
            $table->foreignId('resolved_by_user_id')
                ->nullable()
                ->after('resolution_summary')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('tenant_confirmed_at')->nullable()->after('resolved_by_user_id');
            $table->text('tenant_confirmation_note')->nullable()->after('tenant_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by_user_id');
            $table->dropColumn([
                'resolution_summary',
                'tenant_confirmed_at',
                'tenant_confirmation_note',
            ]);
        });
    }
};
