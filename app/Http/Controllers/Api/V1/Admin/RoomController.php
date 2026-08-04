<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Http\Traits\ApiResponse;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $rooms = Room::latest()->get();

        return $this->successResponse(RoomResource::collection($rooms));
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = Room::create($request->validated());

        return $this->successResponse(new RoomResource($room), 'Ruangan berhasil ditambahkan.', 201);
    }

    public function show(Room $room): JsonResponse
    {
        return $this->successResponse(new RoomResource($room));
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $room->update($request->validated());

        return $this->successResponse(new RoomResource($room), 'Data ruangan berhasil diperbarui.');
    }

    public function destroy(Room $room): JsonResponse
    {
        if ($room->books()->whereIn('status', ['pending', 'approved'])->exists()) {
            return $this->errorResponse('Tidak dapat menghapus ruangan yang memiliki booking aktif atau pending.', 422);
        }

        $room->delete();

        return $this->successResponse(null, 'Ruangan berhasil dihapus.');
    }
}
