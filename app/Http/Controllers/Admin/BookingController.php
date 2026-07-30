<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Book;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = Book::with(['room', 'requester.organization'])
                ->latest();

            // Filter opsional
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }
            if ($request->filled('organization_id')) {
                $query->whereHas('requester', function ($q) use ($request) {
                    $q->where('organization_id', $request->organization_id);
                });
            }
            if ($request->filled('search')) {
                $searchLower = strtolower($request->search);
                $query->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereHas('room', function ($qRoom) use ($searchLower) {
                          $qRoom->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                      })
                      ->orWhereHas('requester', function ($qReq) use ($searchLower) {
                          $qReq->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                      });
                });
            }

            return response()->json([
                'bookings' => $query->paginate(15)->through(fn($b) => [
                    'id'          => $b->id,
                    'title'       => $b->title,
                    'status'      => $b->status,
                    'start_date'  => $b->start_date->format('Y-m-d'),
                    'end_date'    => $b->end_date?->format('Y-m-d'),
                    'start_hour'  => $b->start_hour,
                    'finish_hour' => $b->finish_hour,
                    'notes'       => $b->notes,
                    'room'        => ['id' => $b->room->id, 'name' => $b->room->name],
                    'requester'   => [
                        'id'   => $b->requester->id,
                        'name' => $b->requester->name,
                        'org'  => $b->requester->organization?->shortname,
                    ],
                ]),
                'filters' => $request->only(['status', 'room_id', 'search', 'organization_id']),
                'rooms'   => Room::active()->select('id', 'name')->get(),
                'organizations' => Organization::select('id', 'name', 'shortname')->get(),
            ]);
        }

        return Inertia::render('Admin/Bookings/Index', [
            'rooms'   => Room::active()->select('id', 'name')->get(),
            'organizations' => Organization::select('id', 'name', 'shortname')->get(),
        ]);
    }

    public function approve(Request $request, Book $book)
    {
        abort_if(!$book->isPending(), 422, 'Booking sudah diproses.');

        // Cek konflik jadwal
        if (Book::hasConflict(
            $book->room_id,
            $book->start_date->format('Y-m-d'),
            $book->end_date ? $book->end_date->format('Y-m-d') : $book->start_date->format('Y-m-d'),
            $book->start_hour,
            $book->finish_hour,
            $book->id
        )) {
            return response()->json([
                'message' => "Gagal menyetujui. Ruangan '{$book->room->name}' sudah dipesan pada tanggal dan jam tersebut.",
                'errors' => ['error' => ["Gagal menyetujui. Ruangan '{$book->room->name}' sudah dipesan pada tanggal dan jam tersebut."]],
            ], 422);
        }

        $book->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks,
        ]);

        Cache::forget('pending_bookings_count');

        return response()->json([
            'message' => "Booking '{$book->title}' telah disetujui.",
            'data' => $book,
        ], 200);
    }

    public function reject(Request $request, Book $book)
    {
        // Izinkan pembatalan jika statusnya pending atau approved
        abort_if(!in_array($book->status, ['pending', 'approved']), 422, 'Booking tidak dapat diproses.');

        $request->validate(['remarks' => 'required|string|max:500']);
        $originalStatus = $book->status;

        if ($originalStatus === 'approved') {
            $endDate = \Carbon\Carbon::parse($book->end_date ?? $book->start_date)->startOfDay();
            $limitDate = $endDate->copy()->addDay()->endOfDay(); // Maksimal 1 hari setelah agenda rapat
            
            if (now()->gt($limitDate)) {
                return response()->json([
                    'message' => 'Pemesanan yang sudah disetujui hanya dapat dibatalkan maksimal 1 hari setelah tanggal rapat.',
                ], 422);
            }
        }

        $book->update([
            'status'       => $originalStatus === 'approved' ? 'cancelled' : 'rejected',
            'approved_by'  => Auth::id(),
            'cancelled_at' => $originalStatus === 'approved' ? now() : null,
            'remarks'      => $request->remarks,
        ]);

        Cache::forget('pending_bookings_count');

        $message = $originalStatus === 'approved' ? "Booking '{$book->title}' berhasil dibatalkan." : "Booking '{$book->title}' berhasil ditolak.";
        return response()->json([
            'message' => $message,
            'data' => $book,
        ], 200);
    }

    public function bulkApprove(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:books,id']);

        $bookings = Book::with('room')
            ->whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->get();

        $approvedCount = 0;
        $errors = [];

        foreach ($bookings as $book) {
            if (Book::hasConflict(
                $book->room_id,
                $book->start_date->format('Y-m-d'),
                $book->end_date ? $book->end_date->format('Y-m-d') : $book->start_date->format('Y-m-d'),
                $book->start_hour,
                $book->finish_hour,
                $book->id
            )) {
                $errors[] = "Booking '{$book->title}' di Ruang '{$book->room->name}' bentrok dengan jadwal yang sudah disetujui.";
                continue;
            }

            $book->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $approvedCount++;
        }

        Cache::forget('pending_bookings_count');

        if (count($errors) > 0) {
            return response()->json([
                'message' => "Beberapa booking bentrok.",
                'approved_count' => $approvedCount,
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'message' => $approvedCount . ' booking telah disetujui.',
        ], 200);
    }

    public function bulkReject(Request $request)
    {
        $request->validate([
            'ids'     => 'required|array',
            'ids.*'   => 'integer|exists:books,id',
            'remarks' => 'required|string|max:500',
        ]);

        $bookings = Book::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->get();

        foreach ($bookings as $book) {
            $book->update([
                'status'      => 'rejected',
                'approved_by' => Auth::id(),
                'remarks'     => $request->remarks,
            ]);
        }

        Cache::forget('pending_bookings_count');

        return response()->json([
            'message' => count($request->ids) . ' booking telah ditolak.',
        ], 200);
    }

    public function show(Book $book)
    {
        $book->load(['room', 'requester.organization', 'approver']);

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
            'approver'     => $book->approver ? [
                'name' => $book->approver->name,
            ] : null,
            'approved_at'  => $book->approved_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Bookings/Create', [
            'rooms' => Room::active()->select('id', 'name', 'floor', 'capacity')->get(),
            'users' => User::where('role', '!=', 'admin')->with('organization')->select('id', 'name', 'organization_id')->get(),
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

        if (Book::hasConflict(
            $validated['room_id'],
            $validated['start_date'],
            $validated['end_date'] ?? $validated['start_date'],
            $validated['start_hour'],
            $validated['finish_hour']
        )) {
            return response()->json([
                'message' => 'Ruangan sudah dipesan pada waktu tersebut.',
                'errors' => ['start_date' => ['Ruangan sudah dipesan pada waktu tersebut.']],
            ], 422);
        }

        $user = User::findOrFail($validated['requester_id']);

        $book = Book::create([
            ...$validated,
            'requester_id' => $user->id,
            'pic_name'     => $user->name,
            'status'       => 'approved',
            'approved_by'  => Auth::id(),
            'approved_at'  => now(),
            'end_date'     => $validated['end_date'] ?? $validated['start_date'],
        ]);

        return response()->json([
            'message' => 'Booking ruangan berhasil dibuat dan disetujui otomatis.',
            'data' => $book,
        ], 201);
    }

    public function edit(Book $book): Response
    {
        return Inertia::render('Admin/Bookings/Edit', [
            'book'  => [
                'id'           => $book->id,
                'room_id'      => $book->room_id,
                'requester_id' => $book->requester_id,
                'title'        => $book->title,
                'status'       => $book->status,
                'start_date'   => $book->start_date->format('Y-m-d'),
                'end_date'     => $book->end_date?->format('Y-m-d'),
                'start_hour'   => substr($book->start_hour, 0, 5),
                'finish_hour'  => substr($book->finish_hour, 0, 5),
                'notes'        => $book->notes,
                'participants' => $book->participants,
            ],
            'rooms' => Room::active()->select('id', 'name', 'floor', 'capacity')->get(),
            'users' => User::where('role', '!=', 'admin')->with('organization')->select('id', 'name', 'organization_id')->get(),
        ]);
    }

    public function update(Request $request, Book $book)
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

        $user = User::findOrFail($validated['requester_id']);

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
}

