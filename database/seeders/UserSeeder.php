<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name'     => 'client_a',
            'email'    => 'admin@admin.com',
            'password' => Hash::make('password'),
        ]);

        UserPreference::create([
            'user_id'   => $user->id,
            'platforms' => ['tiktok', 'facebook'],
        ]);
    }
}
