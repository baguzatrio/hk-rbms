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
            'status'  => 'rejected',
            'remarks' => $request->remarks,
        ]);

        Cache::forget('pending_bookings_count');

        $this->logAction('reject', $book);

        return back()->with('success', "Booking '{$book->title}' telah ditolak.");
    }

    public function bulkApprove(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:books,id']);

        Book::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

        Cache::forget('pending_bookings_count');

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
                'status'  => 'rejected',
                'remarks' => $request->remarks,
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
}
