<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Book::with(['room', 'requester.organization', 'approver']);

        // Apply filters
        $this->applyFilters($query, $request);

        $bookings = $query->latest('start_date')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn($b) => [
                'id'          => $b->id,
                'title'       => $b->title,
                'status'      => $b->status,
                'start_date'  => $b->start_date->format('Y-m-d'),
                'end_date'    => $b->end_date?->format('Y-m-d'),
                'start_hour'  => $b->start_hour,
                'finish_hour' => $b->finish_hour,
                'pic_name'    => $b->pic_name,
                'remarks'     => $b->remarks,
                'room'        => [
                    'id'    => $b->room->id,
                    'name'  => $b->room->name,
                    'floor' => $b->room->floor,
                ],
                'requester'   => [
                    'id'   => $b->requester->id,
                    'name' => $b->requester->name,
                    'org'  => $b->requester->organization?->shortname ?? $b->requester->organization?->name,
                ],
                'approver'    => $b->approver ? [
                    'id'   => $b->approver->id,
                    'name' => $b->approver->name,
                ] : null,
            ]);

        $rooms = Room::select('id', 'name', 'floor')->orderBy('name')->get();
        $floors = Room::select('floor')->distinct()->orderBy('floor')->pluck('floor');

        return Inertia::render('Admin/Logs/Index', [
            'bookings' => $bookings,
            'rooms'    => $rooms,
            'floors'   => $floors,
            'filters'  => $request->only(['start_month', 'end_month', 'room_id', 'floor', 'search']),
        ]);
    }

    public function export(Request $request)
    {
        $query = Book::with(['room', 'requester.organization', 'approver']);

        // Apply filters
        $this->applyFilters($query, $request);

        $bookings = $query->latest('start_date')->get();

        $filename = $request->input('filename', 'riwayat_booking');
        // Sanitize filename to prevent directory traversal or invalid characters
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename) . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->streamDownload(function() use ($bookings) {
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM to make it open nicely in Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header columns
            fputcsv($output, [
                'No',
                'Judul Rapat',
                'Ruangan',
                'Lantai',
                'Pemohon',
                'Divisi Pemohon',
                'Tanggal Rapat',
                'Waktu Rapat',
                'Penanggung Jawab (PIC)',
                'Status',
                'Catatan/Remarks',
                'Diproses Oleh',
            ]);

            foreach ($bookings as $index => $b) {
                fputcsv($output, [
                    $index + 1,
                    $b->title,
                    $b->room->name,
                    $b->room->floor,
                    $b->requester->name,
                    $b->requester->organization?->shortname ?? $b->requester->organization?->name ?? '-',
                    $b->start_date->format('d-m-Y') . ($b->end_date ? ' s.d. ' . $b->end_date->format('d-m-Y') : ''),
                    substr($b->start_hour, 0, 5) . ' - ' . substr($b->finish_hour, 0, 5),
                    $b->pic_name ?? '-',
                    ucfirst($b->status),
                    $b->remarks ?? '-',
                    $b->approver?->name ?? '-',
                ]);
            }

            fclose($output);
        }, $filename, $headers);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('start_month')) {
            $start = Carbon::parse($request->start_month . '-01')->startOfMonth()->toDateString();
            $query->whereDate('start_date', '>=', $start);
        }

        if ($request->filled('end_month')) {
            $end = Carbon::parse($request->end_month . '-01')->endOfMonth()->toDateString();
            $query->whereDate('start_date', '<=', $end);
        }

        if ($request->filled('room_id') && $request->room_id !== 'all') {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('floor') && $request->floor !== 'all') {
            $query->whereHas('room', function($q) use ($request) {
                $q->where('floor', $request->floor);
            });
        }

        if ($request->filled('search')) {
            $driver = \DB::connection()->getDriverName();
            $like = $driver === 'pgsql' ? 'ilike' : 'like';
            $search = $request->search;
            $query->where(function($q) use ($search, $like) {
                $q->where('title', $like, "%{$search}%")
                  ->orWhere('pic_name', $like, "%{$search}%")
                  ->orWhereHas('requester', function($q2) use ($search, $like) {
                      $q2->where('name', $like, "%{$search}%");
                  });
            });
        }
    }
}
