<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $roomId = $request->query('room_id');
        $orgId = $request->query('organization_id');
        
        $query = Book::approved()
            ->with(['room', 'requester.organization']);

        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        if ($orgId) {
            $query->whereHas('requester', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            });
        }

        $bookings = $query->get()->map(fn($b) => [
            'id'          => $b->id,
            'title'       => $b->title,
            'start_date'  => $b->start_date->format('Y-m-d'),
            'end_date'    => $b->end_date?->format('Y-m-d'),
            'start_hour'  => $b->start_hour,
            'finish_hour' => $b->finish_hour,
            'room'        => ['id' => $b->room->id, 'name' => $b->room->name, 'floor' => $b->room->floor],
            'requester'   => [
                'name' => $b->requester->name,
                'org'  => $b->requester->organization?->shortname,
            ]
        ]);

        return Inertia::render('JadwalRapat', [
            'bookings'      => $bookings,
            'rooms'         => Room::active()->select('id', 'name', 'floor')->get(),
            'organizations' => \App\Models\Organization::select('id', 'name', 'shortname')->get(),
            'filters'       => $request->only(['room_id', 'organization_id']),
        ]);
    }

    public function show(Room $room): RedirectResponse
    {
        return redirect()->route('schedule.index', ['room_id' => $room->id]);
    }
}

