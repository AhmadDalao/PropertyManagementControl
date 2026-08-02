<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->unique();
            $table->foreignId('portfolio_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notification_class');
            $table->string('email_type', 80);
            $table->string('recipient_email');
            $table->string('subject')->nullable();
            $table->string('status', 24)->default('processing');
            $table->string('mailer', 40);
            $table->string('transport_message_id')->nullable();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['email_type', 'created_at']);
            $table->index(['portfolio_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_delivery_logs');
    }
};
