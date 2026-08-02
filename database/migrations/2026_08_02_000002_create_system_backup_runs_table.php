<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_backup_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('initiated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status', 30)->default('queued')->index();
            $table->string('trigger', 30)->default('manual');
            $table->string('archive_disk', 40)->default('local');
            $table->string('archive_path')->nullable();
            $table->unsignedBigInteger('database_bytes')->default(0);
            $table->unsignedBigInteger('documents_bytes')->default(0);
            $table->unsignedBigInteger('archive_bytes')->default(0);
            $table->unsignedInteger('table_count')->default(0);
            $table->unsignedBigInteger('database_row_count')->default(0);
            $table->unsignedInteger('document_count')->default(0);
            $table->char('archive_sha256', 64)->nullable();
            $table->text('failure_summary')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backup_runs');
    }
};
