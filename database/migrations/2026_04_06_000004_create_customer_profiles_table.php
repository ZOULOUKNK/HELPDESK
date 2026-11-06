<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_vip')->default(false);
            $table->string('default_priority', 50)->nullable();
            $table->string('default_category', 50)->nullable();
            $table->integer('custom_sla_hours')->nullable();
            $table->string('assigned_team', 100)->nullable();
            $table->string('accent_color', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};