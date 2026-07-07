<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Hutama',        'slug' => 'hutama',          'floor' => '8', 'capacity' => 10],
            ['name' => 'Sekar',         'slug' => 'sekar',           'floor' => '8', 'capacity' => 8],
            ['name' => 'Inovasi',       'slug' => 'inovasi',         'floor' => '8', 'capacity' => 10],
            ['name' => 'Karya',         'slug' => 'karya',           'floor' => '8', 'capacity' => 10],
            ['name' => 'Solusi',        'slug' => 'solusi',          'floor' => '5', 'capacity' => 10],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(['slug' => $room['slug']], $room);
        }
    }
}
