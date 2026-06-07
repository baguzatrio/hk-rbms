<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $orgClass = 'App\Models\Organization';
        $tiOrgId = null;
        $sdmOrgId = null;
        if (class_exists($orgClass)) {
            $tiOrgId = $orgClass::where('shortname', 'TI')->first()?->id;
            $sdmOrgId = $orgClass::where('shortname', 'SDM')->first()?->id;
        }

        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@rbms.local'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'role' => 'admin',            // kolom mirror
                'organization_id' => $tiOrgId,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        // User biasa (pengganti mock dika@gmail.com)
        $user = User::firstOrCreate(
            ['email' => 'dika@gmail.com'],
            [
                'name' => 'Dika',
                'password' => 'password',
                'role' => 'user',             // kolom mirror
                'organization_id' => $sdmOrgId,
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles(['user']);
    }
}
