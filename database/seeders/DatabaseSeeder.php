<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seeders = [
            RoleSeeder::class,
        ];

        if (class_exists(OrganizationSeeder::class)) {
            $seeders[] = OrganizationSeeder::class;
        }
        if (class_exists(RoomSeeder::class)) {
            $seeders[] = RoomSeeder::class;
        }

        $seeders[] = UserSeeder::class;

        $this->call($seeders);
    }
}
