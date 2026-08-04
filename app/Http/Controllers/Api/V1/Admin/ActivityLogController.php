<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Activity::with('causer');

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(log_name) LIKE ?', ["%{$search}%"])
                  ->orWhereHasMorph('causer', ['App\Models\User'], fn($u) => $u->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]));
            });
        }

        if ($request->filled('action')) {
            $query->where('log_name', $request->action);
        }

        $logs = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->successResponse(
            ActivityLogResource::collection($logs),
            'Activity logs retrieved.',
            200,
            [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'total'        => $logs->total(),
            ]
        );
    }

    public function export(Request $request)
    {
        $query = Activity::with('causer');

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(log_name) LIKE ?', ["%{$search}%"]);
            });
        }

        $logs = $query->latest()->get();

        $filename = 'log_aktivitas_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($logs) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, ['No', 'Tanggal & Waktu', 'Pengguna', 'Email', 'Modul/Aksi', 'Deskripsi']);

            foreach ($logs as $index => $l) {
                fputcsv($output, [
                    $index + 1,
                    $l->created_at->format('d-m-Y H:i:s'),
                    $l->causer?->name ?? 'Sistem',
                    $l->causer?->email ?? '-',
                    $l->log_name,
                    $l->description ?? '-',
                ]);
            }

            fclose($output);
        }, $filename, $headers);
    }
}
