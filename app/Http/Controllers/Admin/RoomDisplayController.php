<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomDisplayController extends Controller
{
    public function index(): Response
    {
        $rooms = Room::where('is_active', true)
            ->orderBy('floor')
            ->orderBy('name')
            ->get()
            ->groupBy('floor');

        return Inertia::render('Admin/Display/Index', [
            'roomsGroupedByFloor' => $rooms
        ]);
    }
}
