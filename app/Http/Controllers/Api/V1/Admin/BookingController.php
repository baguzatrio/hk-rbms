<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Http\Traits\ApiResponse;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Book::with(['room', 'requester.organization'])->latest();

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
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('room', fn($r) => $r->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
                  ->orWhereHas('requester', fn($u) => $u->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]));
            });
        }

        $bookings = $query->paginate($request->integer('per_page', 15));

        return $this->successResponse(
            BookResource::collection($bookings),
            'Admin bookings list retrieved.',
            200,
            [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ]
        );
    }

    public function approve(Request $request, Book $book): JsonResponse
    {
        if (!$book->isPending()) {
            return $this->errorResponse('Booking sudah diproses.', 422);
        }

        if (Book::hasConflict(
            $book->room_id,
            $book->start_date->format('Y-m-d'),
            $book->end_date ? $book->end_date->format('Y-m-d') : $book->start_date->format('Y-m-d'),
            $book->start_hour,
            $book->finish_hour,
            $book->id
        )) {
            return $this->errorResponse("Gagal menyetujui. Ruangan '{$book->room->name}' sudah dipesan pada tanggal dan jam tersebut.", 422);
        }

        $book->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $this->successResponse(new BookResource($book->fresh(['room', 'requester.organization'])), 'Booking berhasil disetujui.');
    }

    public function reject(Request $request, Book $book): JsonResponse
    {
        if (!$book->isPending()) {
            return $this->errorResponse('Booking sudah diproses.', 422);
        }

        $request->validate([
            'rejection_note' => 'required|string|max:1000',
        ], [
            'rejection_note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $book->update([
            'status'         => 'rejected',
            'rejection_note' => $request->rejection_note,
            'remarks'        => $request->rejection_note,
            'approved_by'    => Auth::id(),
        ]);

        return $this->successResponse(new BookResource($book->fresh(['room', 'requester.organization'])), 'Booking telah ditolak.');
    }

    public function bulkApprove(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:books,id',
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Book> $bookings */
        $bookings = Book::with('room')->whereIn('id', $request->ids)->where('status', 'pending')->get();
        $approvedCount = 0;
        $errors = [];

        /** @var Book $book */
        foreach ($bookings as $book) {
            if (Book::hasConflict(
                $book->room_id,
                $book->start_date->format('Y-m-d'),
                $book->end_date ? $book->end_date->format('Y-m-d') : $book->start_date->format('Y-m-d'),
                $book->start_hour,
                $book->finish_hour,
                $book->id
            )) {
                $errors[] = "Booking '{$book->title}' di Ruang '{$book->room->name}' bentrok dengan jadwal lain.";
                continue;
            }

            $book->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $approvedCount++;
        }

        return $this->successResponse([
            'approved_count' => $approvedCount,
            'errors'         => $errors,
        ], "{$approvedCount} booking berhasil disetujui.");
    }

    public function bulkReject(Request $request): JsonResponse
    {
        $request->validate([
            'ids'            => 'required|array',
            'ids.*'          => 'integer|exists:books,id',
            'rejection_note' => 'required|string|max:1000',
        ], [
            'rejection_note.required' => 'Alasan penolakan wajib diisi untuk bulk reject.',
        ]);

        $affected = Book::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->update([
                'status'         => 'rejected',
                'rejection_note' => $request->rejection_note,
                'remarks'        => $request->rejection_note,
                'approved_by'    => Auth::id(),
            ]);

        return $this->successResponse([
            'rejected_count' => $affected,
        ], "{$affected} booking berhasil ditolak.");
    }
}
