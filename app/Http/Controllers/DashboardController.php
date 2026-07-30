<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Book;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        if ($isAdmin) {
            $roomStats = $this->getRoomStats();
            return Inertia::render('Dashboard/AdminDashboard', [
                'stats' => [
                    'pendingCount'  => Book::pending()->count(),
                    'approvedToday' => $this->activeTodayQuery()->count(),
                    'occupiedRooms' => $roomStats['occupied'],
                    'vacantRooms'   => $roomStats['vacant'],
                    'totalUsers'    => \App\Models\User::count(),
                ],
                'recentPending' => Book::pending()
                    ->with(['room', 'requester.organization'])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn($b) => $this->formatBook($b)),
            ]);
        }

        $roomId = $request->query('room_id');
        $orgId = $request->query('organization_id');

        $calendarQuery = Book::approved()
            ->with(['room', 'requester.organization']);

        if ($roomId) {
            $calendarQuery->where('room_id', $roomId);
        }

        if ($orgId) {
            $calendarQuery->whereHas('requester', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            });
        }

        $calendarBookings = $calendarQuery->get()->map(fn($b) => [
            'id'          => $b->id,
            'title'       => $b->title,
            'start_date'  => $b->start_date->format('Y-m-d'),
            'end_date'    => $b->end_date?->format('Y-m-d'),
            'start_hour'  => $b->start_hour,
            'finish_hour' => $b->finish_hour,
            'room'        => ['id' => $b->room->id, 'name' => $b->room->name],
            'requester'   => [
                'name' => $b->requester->name,
                'org'  => $b->requester->organization?->shortname,
            ]
        ]);

        return Inertia::render('Dashboard/UserDashboard', [
            'myBookings' => Book::where('requester_id', $user->id)
                ->with('room')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($b) => $this->formatBook($b)),
            'todaySchedule' => $this->activeTodayQuery($roomId, $orgId)
                ->with('room')
                ->orderBy('start_hour')
                ->get()
                ->map(fn($b) => $this->formatBook($b)),
            'bookings'      => $calendarBookings,
            'rooms'         => Room::active()->select('id', 'name', 'floor')->get(),
            'organizations' => \App\Models\Organization::select('id', 'name', 'shortname')->get(),
            'filters'       => $request->only(['room_id', 'organization_id']),
        ]);
    }

    private function formatBook(Book $book): array
    {
        return [
            'id'          => $book->id,
            'title'       => $book->title,
            'status'      => $book->status,
            'start_date'  => $book->start_date->format('Y-m-d'),
            'end_date'    => $book->end_date?->format('Y-m-d'),
            'start_hour'  => $book->start_hour,
            'finish_hour' => $book->finish_hour,
            'room'        => $book->room ? [
                'id'   => $book->room->id,
                'name' => $book->room->name,
                'floor'=> $book->room->floor,
            ] : null,
            'requester'   => $book->requester ? [
                'id'   => $book->requester->id,
                'name' => $book->requester->name,
                'organization' => $book->requester->organization?->shortname,
            ] : null,
        ];
    }

    private function getRoomStats(): array
    {
        $nowDate = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        // Fetch bookings that are currently ongoing/active
        $activeBookings = Book::approved()
            ->with('room')
            ->where(function ($q) use ($nowDate) {
                $q->whereDate('start_date', '<=', $nowDate)
                  ->whereDate('end_date', '>=', $nowDate);
            })
            ->whereTime('start_hour', '<=', $nowTime)
            ->whereTime('finish_hour', '>=', $nowTime)
            ->get();

        $occupiedRoomsMap = [];
        foreach ($activeBookings as $booking) {
            if ($booking->room) {
                $occupiedRoomsMap[$booking->room_id] = [
                    'id' => $booking->room->id,
                    'name' => $booking->room->name,
                    'floor' => $booking->room->floor,
                    'booking_title' => $booking->title,
                    'start_hour' => substr($booking->start_hour, 0, 5),
                    'finish_hour' => substr($booking->finish_hour, 0, 5),
                ];
            }
        }

        $allActiveRooms = Room::active()->get(['id', 'name', 'floor']);
        $occupiedRooms = [];
        $vacantRooms = [];

        foreach ($allActiveRooms as $room) {
            if (isset($occupiedRoomsMap[$room->id])) {
                $occupiedRooms[] = $occupiedRoomsMap[$room->id];
            } else {
                $vacantRooms[] = [
                    'id' => $room->id,
                    'name' => $room->name,
                    'floor' => $room->floor,
                ];
            }
        }

        return [
            'occupied' => $occupiedRooms,
            'vacant'   => $vacantRooms,
        ];
    }

    public function stats(Request $request)
    {
        abort_if(!$request->user()->hasRole('admin'), 403);
        $roomStats = $this->getRoomStats();
        return response()->json([
            'pendingCount'  => Book::pending()->count(),
            'approvedToday' => $this->activeTodayQuery()->count(),
            'occupiedRooms' => $roomStats['occupied'],
            'vacantRooms'   => $roomStats['vacant'],
            'totalUsers'    => \App\Models\User::count(),
        ]);
    }

    private function activeTodayQuery($roomId = null, $orgId = null)
    {
        $today = now()->toDateString();
        $query = Book::approved()
            ->where(function ($q) use ($today) {
                $q->whereDate('start_date', '<=', $today)
                  ->whereDate(DB::raw("COALESCE(end_date, start_date)"), '>=', $today);
            });

        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        if ($orgId) {
            $query->whereHas('requester', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            });
        }

        return $query;
    }
}
