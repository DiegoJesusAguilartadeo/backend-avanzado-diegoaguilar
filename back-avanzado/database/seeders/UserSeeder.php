<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Administrador
        User::firstOrCreate(
            ['email' => 'admin@ecommerce.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('Password123!'),
                'role'     => 'admin',
            ]
        );

        // Cliente
        User::firstOrCreate(
            ['email' => 'cliente@ecommerce.com'],
            [
                'name'     => 'Cliente Test',
                'password' => Hash::make('Password123!'),
                'role'     => 'customer',
            ]
        );
    }
}