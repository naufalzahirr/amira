<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@smkn4tpi.sch.id'],
            [
                'name'     => 'Admin AMIRA',
                'password' => Hash::make('password'), // GANTI setelah berhasil login
                'is_admin' => true,
            ]
        );
    }
}
