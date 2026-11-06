<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {

            DB::statement('PRAGMA foreign_keys=OFF;');

            // Supprime l’index existant pour éviter le conflit
            DB::statement('DROP INDEX IF EXISTS users_email_unique');

            // Construire la nouvelle table à côté de l'ancienne. Renommer
            // « users » en « users_old » ferait pointer les clés étrangères
            // des autres tables (tickets, attachments…) vers users_old,
            // supprimée ensuite : toute insertion échouerait.
            Schema::create('users_new', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email'); // PAS de ->unique() ici
                $table->string('password');
                $table->string('role')->default('client');

                $table->string('department')->nullable();
                $table->string('avatar')->nullable();
                $table->dateTime('last_login_at')->nullable();
                $table->string('remember_token')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('phone')->nullable();
                $table->text('bio')->nullable();
            });

            // Copier les données
            DB::statement("
                INSERT INTO users_new (
                    id, name, email, password, role,
                    department, avatar, last_login_at,
                    remember_token, created_at, updated_at,
                    is_active, phone, bio
                )
                SELECT
                    id, name, email, password, role,
                    department, avatar, last_login_at,
                    remember_token, created_at, updated_at,
                    is_active, phone, bio
                FROM users
            ");

            // Supprimer ancienne table
            Schema::drop('users');
            Schema::rename('users_new', 'users');

            // Recréer l’index UNIQUE proprement
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        // Cas MySQL / autres
        DB::statement("
            ALTER TABLE users
            MODIFY role VARCHAR(255) NOT NULL DEFAULT 'client'
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {

            DB::statement('PRAGMA foreign_keys=OFF;');

            DB::statement('DROP INDEX IF EXISTS users_email_unique');

            Schema::create('users_new', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('password');
                $table->string('role')->default('client');

                $table->string('department')->nullable();
                $table->string('avatar')->nullable();
                $table->dateTime('last_login_at')->nullable();
                $table->string('remember_token')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('phone')->nullable();
                $table->text('bio')->nullable();
            });

            DB::statement("
                INSERT INTO users_new (
                    id, name, email, password, role,
                    department, avatar, last_login_at,
                    remember_token, created_at, updated_at,
                    is_active, phone, bio
                )
                SELECT
                    id, name, email, password,
                    CASE
                        WHEN role = 'super_admin' THEN 'admin'
                        ELSE role
                    END,
                    department, avatar, last_login_at,
                    remember_token, created_at, updated_at,
                    is_active, phone, bio
                FROM users
            ");

            Schema::drop('users');
            Schema::rename('users_new', 'users');

            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        DB::statement("
            ALTER TABLE users
            MODIFY role VARCHAR(255) NOT NULL DEFAULT 'client'
        ");
    }
};