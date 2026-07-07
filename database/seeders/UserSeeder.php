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
        $ertiOrgId = null;
        $hcOrgId = null;
        if (class_exists($orgClass)) {
            $ertiOrgId = $orgClass::where('shortname', 'ERTI')->first()?->id;
            $hcOrgId = $orgClass::where('shortname', 'HC')->first()?->id;
        }

        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@rbms.local'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'role' => 'admin',            // kolom mirror
                'organization_id' => $ertiOrgId,
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
                'organization_id' => $hcOrgId,
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles(['user']);
    }
}
