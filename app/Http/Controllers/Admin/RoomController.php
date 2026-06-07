<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Rooms/Index', [
            'rooms' => Room::latest()->paginate(10)
        ]);
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
            'facilities'  => 'nullable|array',
            'is_active'   => 'required|boolean',
            'photo_path'  => 'nullable|string',
        ]);

        Room::create($validated);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Ruangan berhasil dibuat.');
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
            'facilities'  => 'nullable|array',
            'is_active'   => 'required|boolean',
            'photo_path'  => 'nullable|string',
        ]);

        $room->update($validated);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Ruangan berhasil diupdate.');
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Ruangan berhasil dihapus.');
    }
}
