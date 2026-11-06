<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rend user_id nullable dans les tables comments et ticket_replies.
 *
 * Pourquoi ?
 * ----------
 * Quand un utilisateur est supprimé, on met son user_id à NULL
 * dans toutes les tables liées (via User::booted()).
 * Sans cette migration, MySQL/SQLite rejette le UPDATE car la
 * colonne est NOT NULL.
 *
 * Cette migration est SANS PERTE DE DONNÉES : elle ne touche
 * qu'à la contrainte de nullité, pas aux données existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table comments
        Schema::table('comments', function (Blueprint $table) {
            // On supprime d'abord la contrainte FK existante
            $table->dropForeign(['user_id']);

            // On rend la colonne nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // On recrée la FK avec nullOnDelete
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // Table ticket_replies (si elle existe)
        if (Schema::hasTable('ticket_replies')) {
            Schema::table('ticket_replies', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        if (Schema::hasTable('ticket_replies')) {
            Schema::table('ticket_replies', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
};
