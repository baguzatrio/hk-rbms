<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Meeting Room A',  'slug' => 'meeting-room-a',  'floor' => 'Lantai 1', 'capacity' => 10],
            ['name' => 'Meeting Room B',  'slug' => 'meeting-room-b',  'floor' => 'Lantai 1', 'capacity' => 8],
            ['name' => 'Board Room',      'slug' => 'board-room',      'floor' => 'Lantai 2', 'capacity' => 20],
            ['name' => 'Training Room',   'slug' => 'training-room',   'floor' => 'Lantai 2', 'capacity' => 30],
            ['name' => 'Ruang Diskusi A', 'slug' => 'ruang-diskusi-a', 'floor' => 'Lantai 3', 'capacity' => 6],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(['slug' => $room['slug']], $room);
        }
    }
}
