<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'electrical' => 'electricity',
            'hvac' => 'ac',
            'appliance' => 'general',
        ] as $legacy => $canonical) {
            DB::table('maintenance_requests')
                ->where('category', $legacy)
                ->update(['category' => $canonical]);
        }
    }

    public function down(): void
    {
        // Canonical categories are intentionally retained; the legacy values were ambiguous.
    }
};
