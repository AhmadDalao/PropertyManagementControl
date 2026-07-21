<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Existing tenant-safe document types were already downloadable by tenants.
     * Persist that behavior as an explicit portal visibility flag.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('documents')->update(['is_public' => false]);

            DB::table('documents')
                ->whereIn('documentable_type', ['lease', 'App\\Models\\Lease'])
                ->whereIn('type', ['lease_contract', 'signed_contract', 'tenant_statement'])
                ->update(['is_public' => true]);

            DB::table('documents')
                ->whereIn('documentable_type', ['payment', 'App\\Models\\Payment'])
                ->where('type', 'receipt')
                ->update(['is_public' => true]);
        });
    }

    public function down(): void
    {
        DB::table('documents')
            ->whereIn('type', ['lease_contract', 'signed_contract', 'tenant_statement', 'receipt'])
            ->update(['is_public' => false]);
    }
};
