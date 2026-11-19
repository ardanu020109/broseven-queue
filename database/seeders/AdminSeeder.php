<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@brosevenqueue.com'],
            [
                'name' => 'BroSeven! Administrator',
                'username' => 'admin',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
