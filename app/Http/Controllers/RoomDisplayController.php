<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Book;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomDisplayController extends Controller
{
    public function show(Room $room): Response
    {
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->toTimeString();

        // Ambil semua jadwal disetujui untuk hari ini
        $bookings = Book::with('requester.organization')
            ->where('room_id', $room->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderBy('start_hour')
            ->get()
            ->map(fn($b) => [
                'id'           => $b->id,
                'title'        => $b->title,
                'start_hour'   => substr($b->start_hour, 0, 5),
                'finish_hour'  => substr($b->finish_hour, 0, 5),
                'pic_name'     => $b->pic_name ?? $b->requester?->name,
                'division'     => $b->requester?->organization?->name ?? 'Umum',
                'start_date'   => $b->start_date->format('Y-m-d'),
                'end_date'     => $b->end_date ? $b->end_date->format('Y-m-d') : null,
            ]);

        // Cari rapat yang sedang berlangsung saat ini
        $currentMeeting = null;
        foreach ($bookings as $b) {
            $start = $b['start_hour'] . ':00';
            $finish = $b['finish_hour'] . ':00';
            if ($nowTime >= $start && $nowTime <= $finish) {
                $currentMeeting = $b;
                break;
            }
        }

        return Inertia::render('Display/Show', [
            'room'           => [
                'id'         => $room->id,
                'name'       => $room->name,
                'floor'      => $room->floor,
                'capacity'   => $room->capacity,
            ],
            'todayBookings'  => $bookings,
            'currentMeeting' => $currentMeeting,
        ]);
    }
}
