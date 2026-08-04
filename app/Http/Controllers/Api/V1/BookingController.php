<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Http\Traits\ApiResponse;
use App\Models\Book;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $bookings = Book::where('requester_id', $user->id)
            ->with(['room', 'requester.organization'])
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return $this->successResponse(
            BookResource::collection($bookings),
            'Bookings retrieved successfully.',
            200,
            [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ]
        );
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // 1. Mandatory Weekend Restriction
        $startDate = Carbon::parse($validated['start_date']);
        if ($startDate->isWeekend()) {
            return $this->errorResponse('Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).', 422, [
                'start_date' => ['Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).'],
            ]);
        }

        if (!empty($validated['end_date'])) {
            $endDate = Carbon::parse($validated['end_date']);
            if ($endDate->isWeekend()) {
                return $this->errorResponse('Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).', 422, [
                    'end_date' => ['Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).'],
                ]);
            }
        }

        // 2. Schedule Conflict Check
        if (Book::hasConflict(
            (int) $validated['room_id'],
            $validated['start_date'],
            $validated['end_date'] ?? $validated['start_date'],
            $validated['start_hour'],
            $validated['finish_hour']
        )) {
            return $this->errorResponse('Ruangan sudah dipesan pada waktu tersebut.', 422, [
                'start_date' => ['Ruangan sudah dipesan pada waktu tersebut.'],
            ]);
        }

        // 3. User Role Enforcement: Override requester_id to logged in user for non-admins
        if (!$user->hasRole('admin')) {
            $validated['requester_id'] = $user->id;
        }

        $requester = \App\Models\User::findOrFail($validated['requester_id']);

        $book = Book::create([
            ...$validated,
            'pic_name' => $requester->name,
            'status'   => 'pending',
            'end_date' => $validated['end_date'] ?? $validated['start_date'],
        ]);

        return $this->successResponse(
            new BookResource($book->load(['room', 'requester.organization'])),
            'Request booking berhasil dikirim. Menunggu persetujuan admin.',
            201
        );
    }

    public function show(Book $book, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('admin') && (int) $book->requester_id !== (int) $user->id) {
            return $this->errorResponse('Anda tidak memiliki akses ke booking ini.', 403);
        }

        return $this->successResponse(
            new BookResource($book->load(['room', 'requester.organization', 'approver']))
        );
    }

    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('admin')) {
            if ((int) $book->requester_id !== (int) $user->id) {
                return $this->errorResponse('Anda tidak memiliki akses untuk mengubah booking ini.', 403);
            }

            if (!$book->isPending()) {
                return $this->errorResponse('Hanya booking dengan status pending yang dapat diubah.', 422);
            }
        }

        $validated = $request->validated();

        // Weekend Check
        $startDate = Carbon::parse($validated['start_date']);
        if ($startDate->isWeekend()) {
            return $this->errorResponse('Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).', 422, [
                'start_date' => ['Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).'],
            ]);
        }

        if (Book::hasConflict(
            (int) $validated['room_id'],
            $validated['start_date'],
            $validated['end_date'] ?? $validated['start_date'],
            $validated['start_hour'],
            $validated['finish_hour'],
            $book->id
        )) {
            return $this->errorResponse('Ruangan sudah dipesan pada waktu tersebut.', 422, [
                'start_date' => ['Ruangan sudah dipesan pada waktu tersebut.'],
            ]);
        }

        if (!$user->hasRole('admin')) {
            $validated['requester_id'] = $user->id;
        }

        $book->update([
            ...$validated,
            'end_date' => $validated['end_date'] ?? $validated['start_date'],
        ]);

        return $this->successResponse(
            new BookResource($book->fresh(['room', 'requester.organization'])),
            'Booking berhasil diperbarui.'
        );
    }

    public function destroy(Book $book, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('admin') && (int) $book->requester_id !== (int) $user->id) {
            return $this->errorResponse('Anda tidak memiliki akses untuk membatalkan booking ini.', 403);
        }

        if (!$user->hasRole('admin') && !$book->isPending()) {
            return $this->errorResponse('Booking sudah diproses, tidak bisa dibatalkan.', 422);
        }

        $book->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return $this->successResponse(null, 'Booking berhasil dibatalkan.');
    }
}
