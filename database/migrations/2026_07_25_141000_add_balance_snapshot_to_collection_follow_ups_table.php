<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_follow_ups', function (Blueprint $table): void {
            $table->decimal('outstanding_amount_at_contact', 14, 2)
                ->nullable()
                ->after('contacted_at');
        });
    }

    public function down(): void
    {
        Schema::table('collection_follow_ups', function (Blueprint $table): void {
            $table->dropColumn('outstanding_amount_at_contact');
        });
    }
};
