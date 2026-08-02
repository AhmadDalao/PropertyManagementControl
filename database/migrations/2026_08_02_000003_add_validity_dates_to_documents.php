<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->date('issued_on')->nullable()->after('type');
            $table->date('expires_on')->nullable()->after('issued_on');
            $table->index(
                ['portfolio_id', 'expires_on'],
                'documents_portfolio_expiry_idx',
            );
        });

        DB::table('documents')
            ->whereIn('documentable_type', ['lease', 'App\\Models\\Lease'])
            ->whereIn('type', ['lease_contract', 'signed_contract'])
            ->select(['id', 'documentable_id'])
            ->orderBy('id')
            ->chunkById(250, function ($documents): void {
                $leases = DB::table('leases')
                    ->whereIn('id', $documents->pluck('documentable_id')->all())
                    ->get(['id', 'started_at', 'ends_at'])
                    ->keyBy('id');

                foreach ($documents as $document) {
                    $lease = $leases->get($document->documentable_id);

                    if ($lease === null) {
                        continue;
                    }

                    DB::table('documents')
                        ->where('id', $document->id)
                        ->update([
                            'issued_on' => $lease->started_at,
                            'expires_on' => $lease->ends_at,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex('documents_portfolio_expiry_idx');
            $table->dropColumn(['issued_on', 'expires_on']);
        });
    }
};
