<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Comptes support / administration
        |--------------------------------------------------------------------------
        */
        User::updateOrCreate(
            ['email' => 'superadmin@helpdesk.fr'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
                'department' => 'Direction',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@helpdesk.fr'],
            [
                'name' => 'Marie Lecomte',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'department' => 'IT',
            ]
        );

        User::updateOrCreate(
            ['email' => 'thomas@helpdesk.fr'],
            [
                'name' => 'Thomas Bernard',
                'password' => Hash::make('password'),
                'role' => 'agent',
                'is_active' => true,
                'department' => 'Infrastructure',
            ]
        );

        User::updateOrCreate(
            ['email' => 'sophie@helpdesk.fr'],
            [
                'name' => 'Sophie Roux',
                'password' => Hash::make('password'),
                'role' => 'agent',
                'is_active' => true,
                'department' => 'Logiciels',
            ]
        );

        User::updateOrCreate(
            ['email' => 'lucas@helpdesk.fr'],
            [
                'name' => 'Lucas Martin',
                'password' => Hash::make('password'),
                'role' => 'agent',
                'is_active' => true,
                'department' => 'Réseau',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Comptes clients
        |--------------------------------------------------------------------------
        */
        User::updateOrCreate(
            ['email' => 'claire@helpdesk.fr'],
            [
                'name' => 'Claire Dubois',
                'password' => Hash::make('password'),
                'role' => 'client',
                'is_active' => true,
                'department' => 'Comptabilité',
            ]
        );

        User::updateOrCreate(
            ['email' => 'julien@helpdesk.fr'],
            [
                'name' => 'Julien Morel',
                'password' => Hash::make('password'),
                'role' => 'client',
                'is_active' => true,
                'department' => 'Ressources Humaines',
            ]
        );

        User::updateOrCreate(
            ['email' => 'emma@helpdesk.fr'],
            [
                'name' => 'Emma Petit',
                'password' => Hash::make('password'),
                'role' => 'client',
                'is_active' => true,
                'department' => 'Commercial',
            ]
        );

        User::updateOrCreate(
            ['email' => 'nicolas@helpdesk.fr'],
            [
                'name' => 'Nicolas Garnier',
                'password' => Hash::make('password'),
                'role' => 'client',
                'is_active' => true,
                'department' => 'Direction',
            ]
        );

        User::updateOrCreate(
            ['email' => 'laura@helpdesk.fr'],
            [
                'name' => 'Laura Marchand',
                'password' => Hash::make('password'),
                'role' => 'client',
                'is_active' => true,
                'department' => 'Marketing',
            ]
        );
    }
}