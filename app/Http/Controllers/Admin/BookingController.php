<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function index(Request $request): Response
    {
        $query = Book::with(['room', 'requester.organization'])
            ->latest();

        // Filter opsional
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }
        if ($request->filled('search')) {
            $driver = \DB::connection()->getDriverName();
            $likeOperator = $driver === 'pgsql' ? 'ilike' : 'like';
            $query->where('title', $likeOperator, "%{$request->search}%");
        }

        return Inertia::render('Admin/Bookings/Index', [
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
            'filters' => $request->only(['status', 'room_id', 'search']),
            'rooms'   => Room::active()->select('id', 'name')->get(),
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
            return back()->withErrors([
                'error' => "Gagal menyetujui. Ruangan '{$book->room->name}' sudah dipesan pada tanggal dan jam tersebut."
            ]);
        }

        $book->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks,
        ]);

        Cache::forget('pending_bookings_count');

        $this->logAction('approve', $book);

        return back()->with('success', "Booking '{$book->title}' telah disetujui.");
    }

    public function reject(Request $request, Book $book)
    {
        abort_if(!$book->isPending(), 422, 'Booking sudah diproses.');

        $request->validate(['remarks' => 'required|string|max:500']);

        $book->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'remarks'     => $request->remarks,
        ]);

        Cache::forget('pending_bookings_count');

        $this->logAction('reject', $book);

        return back()->with('success', "Booking '{$book->title}' telah ditolak.");
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

            $this->logAction('approve', $book);
            $approvedCount++;
        }

        Cache::forget('pending_bookings_count');

        if (count($errors) > 0) {
            if ($approvedCount > 0) {
                return back()->with('success', "{$approvedCount} booking berhasil disetujui.")
                    ->withErrors(['error' => implode("\n", $errors)]);
            }
            return back()->withErrors(['error' => implode("\n", $errors)]);
        }

        return back()->with('success', count($request->ids) . ' booking telah disetujui.');
    }

    public function bulkReject(Request $request)
    {
        $request->validate([
            'ids'     => 'required|array',
            'ids.*'   => 'integer|exists:books,id',
            'remarks' => 'required|string|max:500',
        ]);

        Book::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->update([
                'status'      => 'rejected',
                'approved_by' => Auth::id(),
                'remarks'     => $request->remarks,
            ]);

        Cache::forget('pending_bookings_count');

        return back()->with('success', count($request->ids) . ' booking telah ditolak.');
    }

    private function logAction(string $action, Book $book): void
    {
        if (class_exists(\App\Models\Log::class)) {
            \App\Models\Log::create([
                'user_id'    => Auth::id(),
                'action'     => $action,
                'model_type' => Book::class,
                'model_id'   => $book->id,
                'description'=> "{$action} booking: {$book->title}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
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
            'pic_name'   => 'required|string|max:255',
            'participants'=> 'nullable|string|max:2000',
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
            'pic_name.required'        => 'Nama PIC penanggung jawab wajib diisi.',
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

        if (Book::hasConflict(
            $validated['room_id'],
            $validated['start_date'],
            $validated['end_date'] ?? $validated['start_date'],
            $validated['start_hour'],
            $validated['finish_hour']
        )) {
            return back()->withErrors([
                'start_date' => 'Ruangan sudah dipesan pada waktu tersebut.'
            ]);
        }

        $book = Book::create([
            ...$validated,
            'requester_id' => Auth::id(),
            'status'       => 'approved',
            'approved_by'  => Auth::id(),
            'approved_at'  => now(),
            'end_date'     => $validated['end_date'] ?? $validated['start_date'],
        ]);

        $this->logAction('create & approve', $book);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Reservasi ruangan berhasil dibuat dan disetujui otomatis.');
    }
}

