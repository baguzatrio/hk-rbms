<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\RoomResource;
use App\Http\Traits\ApiResponse;
use App\Models\Book;
use App\Models\Organization;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function userDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $roomId = $request->query('room_id');
        $orgId = $request->query('organization_id');
        $today = Carbon::today()->format('Y-m-d');

        // User's own latest bookings
        $myBookings = Book::where('requester_id', $user->id)
            ->with('room')
            ->latest()
            ->take(10)
            ->get();

        // Today's schedule (including multi-day meetings spanning today)
        $todayQuery = Book::approved()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with(['room', 'requester.organization']);

        if ($roomId) {
            $todayQuery->where('room_id', $roomId);
        }

        if ($orgId) {
            $todayQuery->whereHas('requester', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            });
        }

        $todaySchedule = $todayQuery->orderBy('start_hour')->get();

        // Approved bookings for interactive calendar grid
        $calendarQuery = Book::approved()->with(['room', 'requester.organization']);

        if ($roomId) {
            $calendarQuery->where('room_id', $roomId);
        }

        if ($orgId) {
            $calendarQuery->whereHas('requester', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            });
        }

        $calendarBookings = $calendarQuery->get();

        return $this->successResponse([
            'my_bookings'    => BookResource::collection($myBookings),
            'today_schedule' => BookResource::collection($todaySchedule),
            'calendar_events'=> BookResource::collection($calendarBookings),
            'rooms'          => RoomResource::collection(Room::active()->get()),
            'organizations'  => OrganizationResource::collection(Organization::all()),
            'filters'        => [
                'room_id'         => $roomId ? (int) $roomId : null,
                'organization_id' => $orgId ? (int) $orgId : null,
            ],
        ]);
    }

    public function adminDashboard(Request $request): JsonResponse
    {
        $today = Carbon::today()->format('Y-m-d');

        $activeTodayCount = Book::approved()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        $occupiedRoomIds = Book::approved()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('room_id')
            ->unique();

        $totalActiveRooms = Room::active()->count();
        $occupiedCount = $occupiedRoomIds->count();
        $vacantCount = max(0, $totalActiveRooms - $occupiedCount);

        $recentPending = Book::pending()
            ->with(['room', 'requester.organization'])
            ->latest()
            ->take(5)
            ->get();

        return $this->successResponse([
            'stats' => [
                'pending_count'  => Book::pending()->count(),
                'approved_today' => $activeTodayCount,
                'occupied_rooms' => $occupiedCount,
                'vacant_rooms'   => $vacantCount,
                'total_users'    => User::count(),
            ],
            'recent_pending' => BookResource::collection($recentPending),
        ]);
    }
}
