<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $search = $request->input('search');
            $perPage = $request->input('per_page', 10);

            if (!in_array($perPage, [10, 20, 30])) {
                $perPage = 10;
            }

            $rooms = Room::latest()
                ->when($search, function ($query, $search) {
                    $searchLower = strtolower($search);
                    $query->where(function ($q) use ($searchLower) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(floor) LIKE ?', ["%{$searchLower}%"]);
                    });
                })
                ->paginate($perPage)
                ->withQueryString();

            return response()->json([
                'rooms' => $rooms,
                'filters' => [
                    'search' => $search,
                    'per_page' => (int) $perPage,
                ],
            ]);
        }

        return Inertia::render('Admin/Rooms/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Rooms/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|unique:rooms,slug',
            'floor'       => 'required|string|max:255',
            'capacity'    => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active'   => 'required|boolean',
            'photo_path'  => 'nullable|string',
        ]);

        $room = Room::create($validated);

        return response()->json([
            'message' => 'Ruangan berhasil dibuat.',
            'data' => $room,
        ], 201);
    }

    public function show(Room $room): Response
    {
        return Inertia::render('Admin/Rooms/Show', [
            'room' => $room
        ]);
    }

    public function edit(Room $room): Response
    {
        return Inertia::render('Admin/Rooms/Edit', [
            'room' => $room
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|unique:rooms,slug,' . $room->id,
            'floor'       => 'required|string|max:255',
            'capacity'    => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active'   => 'required|boolean',
            'photo_path'  => 'nullable|string',
        ]);

        $room->update($validated);

        return response()->json([
            'message' => 'Ruangan berhasil diupdate.',
            'data' => $room,
        ], 200);
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json([
            'message' => 'Ruangan berhasil dihapus.',
        ], 200);
    }
}
