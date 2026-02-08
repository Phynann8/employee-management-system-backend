<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;

class RepairSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure Admin Role exists
        $role = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['level' => 10]
        );

        // Ensure Admin User exists and reset password
        $user = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'password' => Hash::make('password'),
                'role_id' => $role->id
            ]
        );
        
        $this->command->info("Admin Account Repaired: {$user->email} / password");
    }
}
