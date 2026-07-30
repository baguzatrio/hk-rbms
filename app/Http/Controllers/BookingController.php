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
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $bookings = Book::where('requester_id', Auth::id())
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
                ]);

            return response()->json([
                'bookings' => $bookings,
            ]);
        }

        return Inertia::render('Bookings/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Bookings/Create', [
            'rooms' => Room::active()->select('id', 'name', 'floor', 'capacity')->get(),
            'users' => \App\Models\User::where('id', Auth::id())->with('organization')->select('id', 'name', 'organization_id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id'      => 'required|exists:rooms,id',
            'title'        => 'required|string|max:255',
            'start_date'   => 'required|date|after_or_equal:today',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'start_hour'   => 'required|date_format:H:i',
            'finish_hour'  => 'required|date_format:H:i|after:start_hour',
            'notes'        => 'nullable|string|max:1000',
            'requester_id' => 'required|exists:users,id',
            'participants' => 'nullable|string|max:2000',
        ], [
            'room_id.required'         => 'Ruangan rapat wajib dipilih.',
            'room_id.exists'           => 'Ruangan rapat tidak valid.',
            'title.required'           => 'Agenda rapat wajib diisi.',
            'start_date.required'      => 'Tanggal rapat wajib diisi.',
            'start_date.after_or_equal'=> 'Tanggal rapat tidak boleh sebelum hari ini.',
            'end_date.after_or_equal'  => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'start_hour.required'      => 'Jam mulai wajib diisi.',
            'finish_hour.required'     => 'Jam selesai wajib diisi.',
            'finish_hour.after'        => 'Jam selesai harus setelah jam mulai.',
            'requester_id.required'    => 'Pemohon rapat wajib dipilih.',
        ]);

        // Cek hari libur (Sabtu & Minggu)
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        if ($startDate->isWeekend()) {
            return response()->json([
                'message' => 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).',
                'errors' => ['start_date' => ['Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).']],
            ], 422);
        }

        if (!empty($validated['end_date'])) {
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            if ($endDate->isWeekend()) {
                return response()->json([
                    'message' => 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).',
                    'errors' => ['end_date' => ['Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).']],
                ], 422);
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
            return response()->json([
                'message' => 'Ruangan sudah dipesan pada waktu tersebut.',
                'errors' => ['start_date' => ['Ruangan sudah dipesan pada waktu tersebut.']],
            ], 422);
        }

        // User hanya bisa membuat booking untuk dirinya sendiri
        $validated['requester_id'] = Auth::id();
        $user = \App\Models\User::findOrFail($validated['requester_id']);

        $book = Book::create([
            ...$validated,
            'requester_id' => $user->id,
            'pic_name'     => $user->name,
            'status'       => 'pending',
            'end_date'     => $validated['end_date'] ?? $validated['start_date'],
        ]);

        return response()->json([
            'message' => 'Request booking berhasil dikirim. Menunggu persetujuan admin.',
            'data' => $book,
        ], 201);
    }

    public function edit(Book $book): Response
    {
        abort_if((int) $book->requester_id !== (int) Auth::id(), 403, 'Anda tidak memiliki akses untuk mengubah booking ini.');
        abort_if($book->status !== 'pending', 403, 'Hanya booking dengan status pending yang dapat diubah.');

        return Inertia::render('Bookings/Edit', [
            'book'  => [
                'id'           => $book->id,
                'room_id'      => $book->room_id,
                'requester_id' => $book->requester_id,
                'title'        => $book->title,
                'start_date'   => $book->start_date->format('Y-m-d'),
                'end_date'     => $book->end_date?->format('Y-m-d'),
                'start_hour'   => substr($book->start_hour, 0, 5),
                'finish_hour'  => substr($book->finish_hour, 0, 5),
                'notes'        => $book->notes,
                'participants' => $book->participants,
            ],
            'rooms' => Room::active()->select('id', 'name', 'floor', 'capacity')->get(),
            'users' => \App\Models\User::where('id', Auth::id())->with('organization')->select('id', 'name', 'organization_id')->get(),
        ]);
    }

    public function update(Request $request, Book $book)
    {
        abort_if((int) $book->requester_id !== (int) Auth::id(), 403, 'Anda tidak memiliki akses untuk mengubah booking ini.');
        abort_if($book->status !== 'pending', 403, 'Hanya booking dengan status pending yang dapat diubah.');

        $validated = $request->validate([
            'room_id'      => 'required|exists:rooms,id',
            'title'        => 'required|string|max:255',
            'start_date'   => 'required|date|after_or_equal:today',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'start_hour'   => 'required|date_format:H:i',
            'finish_hour'  => 'required|date_format:H:i|after:start_hour',
            'notes'        => 'nullable|string|max:1000',
            'requester_id' => 'required|exists:users,id',
            'participants' => 'nullable|string|max:2000',
        ], [
            'room_id.required'         => 'Ruangan rapat wajib dipilih.',
            'room_id.exists'           => 'Ruangan rapat tidak valid.',
            'title.required'           => 'Agenda rapat wajib diisi.',
            'start_date.required'      => 'Tanggal rapat wajib diisi.',
            'start_date.after_or_equal'=> 'Tanggal rapat tidak boleh sebelum hari ini.',
            'end_date.after_or_equal'  => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'start_hour.required'      => 'Jam mulai wajib diisi.',
            'finish_hour.required'     => 'Jam selesai wajib diisi.',
            'finish_hour.after'        => 'Jam selesai harus setelah jam mulai.',
            'requester_id.required'    => 'Pemohon rapat wajib dipilih.',
        ]);

        // Cek hari libur (Sabtu & Minggu)
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        if ($startDate->isWeekend()) {
            return response()->json([
                'message' => 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).',
                'errors' => ['start_date' => ['Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).']],
            ], 422);
        }

        if (!empty($validated['end_date'])) {
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            if ($endDate->isWeekend()) {
                return response()->json([
                    'message' => 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).',
                    'errors' => ['end_date' => ['Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).']],
                ], 422);
            }
        }

        // Cek konflik jadwal (kecuali booking ini sendiri)
        if (Book::hasConflict(
            $validated['room_id'],
            $validated['start_date'],
            $validated['end_date'] ?? $validated['start_date'],
            $validated['start_hour'],
            $validated['finish_hour'],
            $book->id
        )) {
            return response()->json([
                'message' => 'Ruangan sudah dipesan pada waktu tersebut.',
                'errors' => ['start_date' => ['Ruangan sudah dipesan pada waktu tersebut.']],
            ], 422);
        }

        // User hanya bisa memperbarui booking untuk dirinya sendiri
        $validated['requester_id'] = Auth::id();
        $user = \App\Models\User::findOrFail($validated['requester_id']);

        $book->update([
            ...$validated,
            'requester_id' => $user->id,
            'pic_name'     => $user->name,
            'end_date'     => $validated['end_date'] ?? $validated['start_date'],
        ]);

        return response()->json([
            'message' => 'Booking berhasil diperbarui.',
            'data'    => $book,
        ], 200);
    }

    public function destroy(Book $book)
    {
        // User hanya bisa cancel booking miliknya sendiri yang masih pending
        abort_if((int) $book->requester_id !== (int) Auth::id(), 403);
        
        if (!$book->isPending()) {
            return response()->json([
                'message' => 'Booking sudah diproses, tidak bisa dibatalkan.',
            ], 422);
        }

        $book->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Booking berhasil dibatalkan.',
        ], 200);
    }

    public function show(Book $book)
    {
        abort_if((int) $book->requester_id !== (int) Auth::id(), 403);

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

