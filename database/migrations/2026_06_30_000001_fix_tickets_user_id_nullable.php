<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Corrige user_id dans tickets : cascade → nullOnDelete
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        // Corrige user_id dans comments si la table existe
        if (Schema::hasTable('comments')) {
            Schema::table('comments', function (Blueprint $table) {
                try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->nullOnDelete();
            });
        }

        // Corrige user_id dans ticket_replies si la table existe
        if (Schema::hasTable('ticket_replies')) {
            Schema::table('ticket_replies', function (Blueprint $table) {
                try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
