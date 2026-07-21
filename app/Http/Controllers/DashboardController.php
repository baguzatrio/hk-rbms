<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Book;
use App\Models\Room;

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
                    'approvedToday' => Book::approved()
                        ->whereDate('start_date', today())
                        ->count(),
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

        return Inertia::render('Dashboard/UserDashboard', [
            'myBookings' => Book::where('requester_id', $user->id)
                ->with('room')
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($b) => $this->formatBook($b)),
            'todaySchedule' => Book::approved()
                ->whereDate('start_date', today())
                ->with('room')
                ->orderBy('start_hour')
                ->get()
                ->map(fn($b) => $this->formatBook($b)),
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

        $occupiedRoomIds = Book::approved()
            ->where(function ($q) use ($nowDate) {
                $q->whereDate('start_date', '<=', $nowDate)
                  ->whereDate('end_date', '>=', $nowDate);
            })
            ->whereTime('start_hour', '<=', $nowTime)
            ->whereTime('finish_hour', '>=', $nowTime)
            ->pluck('room_id')
            ->unique()
            ->toArray();

        $allActiveRooms = Room::active()->get(['id', 'name', 'floor']);
        $occupiedRooms = [];
        $vacantRooms = [];

        foreach ($allActiveRooms as $room) {
            if (in_array($room->id, $occupiedRoomIds)) {
                $occupiedRooms[] = [
                    'id' => $room->id,
                    'name' => $room->name,
                    'floor' => $room->floor,
                ];
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
            'approvedToday' => Book::approved()
                ->whereDate('start_date', today())
                ->count(),
            'occupiedRooms' => $roomStats['occupied'],
            'vacantRooms'   => $roomStats['vacant'],
            'totalUsers'    => \App\Models\User::count(),
        ]);
    }
}
