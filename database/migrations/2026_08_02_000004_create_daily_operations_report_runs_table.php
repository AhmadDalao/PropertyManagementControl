<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_operations_report_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('initiated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status', 30)->default('queued')->index();
            $table->string('trigger', 30)->default('manual');
            $table->date('report_date');
            $table->string('schedule_key')->nullable()->unique();
            $table->string('storage_disk', 40)->default('local');
            $table->string('pdf_path')->nullable();
            $table->string('docx_path')->nullable();
            $table->string('xlsx_path')->nullable();
            $table->unsignedBigInteger('pdf_bytes')->default(0);
            $table->unsignedBigInteger('docx_bytes')->default(0);
            $table->unsignedBigInteger('xlsx_bytes')->default(0);
            $table->unsignedInteger('item_count')->default(0);
            $table->json('summary_json')->nullable();
            $table->json('scope_json')->nullable();
            $table->text('failure_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['portfolio_id', 'report_date']);
            $table->index(['status', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_operations_report_runs');
    }
};
