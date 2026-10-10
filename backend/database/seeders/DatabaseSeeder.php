<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@supportdesk.test'],
            ['name' => 'Avery Blake', 'password' => 'password', 'role' => Role::Administrator]
        );

        User::firstOrCreate(
            ['email' => 'agent@supportdesk.test'],
            ['name' => 'Jordan Reed', 'password' => 'password', 'role' => Role::Agent]
        );

        User::firstOrCreate(
            ['email' => 'customer@supportdesk.test'],
            ['name' => 'Riley Chen', 'password' => 'password', 'role' => Role::Customer]
        );
    }
}
