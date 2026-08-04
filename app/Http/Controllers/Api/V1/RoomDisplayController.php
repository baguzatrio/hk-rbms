<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Http\Resources\RoomResource;
use App\Http\Traits\ApiResponse;
use App\Models\Book;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class RoomDisplayController extends Controller
{
    use ApiResponse;

    public function show(Room $room): JsonResponse
    {
        $today = Carbon::today()->format('Y-m-d');
        $nowTime = Carbon::now()->format('H:i:s');

        // Current Meeting (Approved, Date spans today, start_hour <= now <= finish_hour)
        $currentMeeting = Book::where('room_id', $room->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('start_hour', '<=', $nowTime)
            ->where('finish_hour', '>=', $nowTime)
            ->with(['requester.organization'])
            ->first();

        // Next Meetings (Approved, Date spans today, start_hour > now)
        $nextMeetings = Book::where('room_id', $room->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('start_hour', '>', $nowTime)
            ->with(['requester.organization'])
            ->orderBy('start_hour')
            ->get();

        return $this->successResponse([
            'room'            => new RoomResource($room),
            'current_meeting' => $currentMeeting ? new BookResource($currentMeeting) : null,
            'next_meetings'    => BookResource::collection($nextMeetings),
        ]);
    }
}
