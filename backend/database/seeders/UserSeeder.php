<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const ACCOUNTS = [
        ['email' => 'admin@supportdesk.test', 'name' => 'Avery Blake', 'role' => Role::Administrator],
        ['email' => 'agent@supportdesk.test', 'name' => 'Jordan Reed', 'role' => Role::Agent],
        ['email' => 'sam@supportdesk.test', 'name' => 'Sam Okafor', 'role' => Role::Agent],
        ['email' => 'priya@supportdesk.test', 'name' => 'Priya Nair', 'role' => Role::Agent],
        ['email' => 'customer@supportdesk.test', 'name' => 'Riley Chen', 'role' => Role::Customer],
        ['email' => 'pat@supportdesk.test', 'name' => 'Pat Morgan', 'role' => Role::Customer],
        ['email' => 'lee@supportdesk.test', 'name' => 'Lee Tanaka', 'role' => Role::Customer],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $account) {
            User::firstOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'role' => $account['role'], 'password' => 'password']
            );
        }
    }
}
