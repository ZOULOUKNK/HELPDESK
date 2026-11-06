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

            // Renommer ancienne table
            Schema::rename('users', 'users_old');

            // Recréer table SANS unique sur email (important)
            Schema::create('users', function (Blueprint $table) {
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
                INSERT INTO users (
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
                FROM users_old
            ");

            // Supprimer ancienne table
            Schema::drop('users_old');

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

            Schema::rename('users', 'users_old');

            Schema::create('users', function (Blueprint $table) {
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
                INSERT INTO users (
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
                FROM users_old
            ");

            Schema::drop('users_old');

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