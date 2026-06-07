<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $orgs = [
            ['name' => 'Teknologi Informasi',  'shortname' => 'TI'],
            ['name' => 'Sumber Daya Manusia',  'shortname' => 'SDM'],
            ['name' => 'Keuangan & Akuntansi', 'shortname' => 'KEU'],
            ['name' => 'Operasional',          'shortname' => 'OPS'],
        ];

        foreach ($orgs as $org) {
            Organization::firstOrCreate(['shortname' => $org['shortname']], $org);
        }
    }
}
