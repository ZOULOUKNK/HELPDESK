<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Une base peut déjà contenir une table customer_profiles vide
        // (id + timestamps) créée par l'ancienne migration du 11/04/2026,
        // supprimée depuis : on complète alors ses colonnes au lieu de
        // la recréer.
        if (Schema::hasTable('customer_profiles')) {
            Schema::table('customer_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('customer_profiles', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
                }
                if (!Schema::hasColumn('customer_profiles', 'is_vip')) {
                    $table->boolean('is_vip')->default(false);
                }
                if (!Schema::hasColumn('customer_profiles', 'default_priority')) {
                    $table->string('default_priority', 50)->nullable();
                }
                if (!Schema::hasColumn('customer_profiles', 'default_category')) {
                    $table->string('default_category', 50)->nullable();
                }
                if (!Schema::hasColumn('customer_profiles', 'custom_sla_hours')) {
                    $table->integer('custom_sla_hours')->nullable();
                }
                if (!Schema::hasColumn('customer_profiles', 'assigned_team')) {
                    $table->string('assigned_team', 100)->nullable();
                }
                if (!Schema::hasColumn('customer_profiles', 'accent_color')) {
                    $table->string('accent_color', 20)->nullable();
                }
            });

            return;
        }

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
