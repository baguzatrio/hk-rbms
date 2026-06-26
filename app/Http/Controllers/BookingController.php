<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Bookings/Index', [
            'bookings' => Book::where('requester_id', Auth::id())
                ->with('room')
                ->latest()
                ->paginate(10)
                ->through(fn($b) => [
                    'id'         => $b->id,
                    'title'      => $b->title,
                    'status'     => $b->status,
                    'start_date' => $b->start_date->format('Y-m-d'),
                    'end_date'   => $b->end_date?->format('Y-m-d'),
                    'start_hour' => $b->start_hour,
                    'finish_hour'=> $b->finish_hour,
                    'room'       => ['id' => $b->room->id, 'name' => $b->room->name],
                    'can_cancel' => $b->isPending(),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Bookings/Create', [
            'rooms' => Room::active()->select('id', 'name', 'floor', 'capacity')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id'    => 'required|exists:rooms,id',
            'title'      => 'required|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'start_hour' => 'required|date_format:H:i',
            'finish_hour'=> 'required|date_format:H:i|after:start_hour',
            'notes'      => 'nullable|string|max:1000',
            'pic_name'   => 'nullable|string|max:255',
            'participants'=> 'nullable|string|max:2000',
        ]);

        // Cek hari libur (Sabtu & Minggu)
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        if ($startDate->isWeekend()) {
            return back()->withErrors([
                'start_date' => 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).'
            ])->withInput();
        }

        if (!empty($validated['end_date'])) {
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            if ($endDate->isWeekend()) {
                return back()->withErrors([
                    'end_date' => 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).'
                ])->withInput();
            }
        }

        // Cek konflik jadwal
        if (Book::hasConflict(
            $validated['room_id'],
            $validated['start_date'],
            $validated['end_date'] ?? $validated['start_date'],
            $validated['start_hour'],
            $validated['finish_hour'],
        )) {
            return back()->withErrors([
                'start_date' => 'Ruangan sudah dipesan pada waktu tersebut.'
            ]);
        }

        Book::create([
            ...$validated,
            'requester_id' => Auth::id(),
            'status'       => 'pending',
            'end_date'     => $validated['end_date'] ?? $validated['start_date'],
        ]);

        return redirect()->route('bookings.index')
            ->with('success', 'Request booking berhasil dikirim. Menunggu persetujuan admin.');
    }

    public function destroy(Book $book)
    {
        // User hanya bisa cancel booking miliknya sendiri yang masih pending
        abort_if($book->requester_id !== Auth::id(), 403);
        abort_if(!$book->isPending(), 422, 'Booking sudah diproses, tidak bisa dibatalkan.');

        $book->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Booking berhasil dibatalkan.');
    }

    public function show(Book $book)
    {
        abort_if($book->requester_id !== Auth::id(), 403);

        $book->load(['room', 'requester.organization']);

        return response()->json([
            'id'           => $book->id,
            'title'        => $book->title,
            'status'       => $book->status,
            'start_date'   => $book->start_date->format('Y-m-d'),
            'end_date'     => $book->end_date?->format('Y-m-d'),
            'start_hour'   => substr($book->start_hour, 0, 5),
            'finish_hour'  => substr($book->finish_hour, 0, 5),
            'notes'        => $book->notes,
            'remarks'      => $book->remarks,
            'pic_name'     => $book->pic_name,
            'participants' => $book->participants,
            'room'         => [
                'name'     => $book->room->name,
                'floor'    => $book->room->floor,
                'capacity' => $book->room->capacity,
            ],
            'requester'    => [
                'name' => $book->requester->name,
                'org'  => $book->requester->organization?->name,
            ],
        ]);
    }
}

