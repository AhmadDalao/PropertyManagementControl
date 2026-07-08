<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('status')->default('active')->index();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Saudi Arabia');
            $table->text('address')->nullable();
            $table->string('default_currency', 3)->default('SAR');
            $table->json('module_settings')->nullable();
            $table->json('theme_settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
