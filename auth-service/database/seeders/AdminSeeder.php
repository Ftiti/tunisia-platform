<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@tunisia-platform.tn'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('Admin2024'),
                'role'      => 'super_admin',
                'phone'     => '99000000',
                'is_active' => true,
            ]
        );

        // Corriger le rôle si le compte existait déjà avec un mauvais rôle
        if ($admin->role !== 'super_admin') {
            $admin->update(['role' => 'super_admin']);
        }
    }
}
