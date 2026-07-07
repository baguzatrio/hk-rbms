<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $orgs = [
            ['name' => 'Engineering, Riset, & Teknologi Informasi', 'shortname' => 'ERTI'],
            ['name' => 'Human Capital', 'shortname' => 'HC'],
            ['name' => 'Akutansi & Keuangan', 'shortname' => 'AKKU'],
            ['name' => 'Infrastuktur I', 'shortname' => 'INFRA-I'],
            ['name' => 'Infrastuktur II', 'shortname' => 'INFRA-II'],
            ['name' => 'Supply Chain Management', 'shortname' => 'SCM'],
        ];

        foreach ($orgs as $org) {
            Organization::firstOrCreate(['shortname' => $org['shortname']], $org);
        }
    }
}
