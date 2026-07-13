<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::with('causer');

        // Apply filters
        $this->applyFilters($query, $request);

        $logs = $query->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn($l) => [
                'id'          => $l->id,
                'action'      => $l->log_name,
                'model_type'  => $l->subject_type ? class_basename($l->subject_type) : null,
                'model_id'    => $l->subject_id,
                'description' => $l->description,
                'event'       => $l->event,
                'ip_address'  => $l->getProperty('ip_address'),
                'user_agent'  => $l->getProperty('user_agent'),
                'properties'  => $l->properties,
                'created_at'  => $l->created_at->format('Y-m-d H:i:s'),
                'user'        => $l->causer ? [
                    'id'   => $l->causer->id,
                    'name' => $l->causer->name,
                    'email'=> $l->causer->email,
                ] : null,
            ]);

        // Get unique action types from db for filter dropdown
        $actions = Activity::select('log_name')->distinct()->orderBy('log_name')->pluck('log_name');

        return Inertia::render('Admin/Logs/Index', [
            'logs'    => $logs,
            'actions' => $actions,
            'filters' => $request->only(['start_month', 'end_month', 'action_filter', 'search']),
        ]);
    }

    public function export(Request $request)
    {
        $query = Activity::with('causer');

        // Apply filters
        $this->applyFilters($query, $request);

        $logs = $query->latest()->get();

        $filename = $request->input('filename', 'log_aktivitas');
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename) . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->streamDownload(function() use ($logs) {
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header columns
            fputcsv($output, [
                'No',
                'Tanggal & Waktu',
                'Nama Pengguna',
                'Email',
                'Aksi',
                'Deskripsi',
                'IP Address',
                'Browser/Device',
            ]);

            foreach ($logs as $index => $l) {
                fputcsv($output, [
                    $index + 1,
                    $l->created_at->format('d-m-Y H:i:s'),
                    $l->causer?->name ?? 'Sistem / Hapus',
                    $l->causer?->email ?? '-',
                    $l->log_name,
                    $l->description ?? '-',
                    $l->getProperty('ip_address') ?? '-',
                    $l->getProperty('user_agent') ?? '-',
                ]);
            }

            fclose($output);
        }, $filename, $headers);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('start_month')) {
            $start = Carbon::parse($request->start_month . '-01')->startOfMonth()->toDateTimeString();
            $query->where('created_at', '>=', $start);
        }

        if ($request->filled('end_month')) {
            $end = Carbon::parse($request->end_month . '-01')->endOfMonth()->toDateTimeString();
            $query->where('created_at', '<=', $end);
        }

        if ($request->filled('action_filter') && $request->action_filter !== 'all') {
            $query->where('log_name', $request->action_filter);
        }

        if ($request->filled('search')) {
            $driver = \DB::connection()->getDriverName();
            $like = $driver === 'pgsql' ? 'ilike' : 'like';
            $search = $request->search;
            $query->where(function($q) use ($search, $like) {
                $q->where('description', $like, "%{$search}%")
                  ->orWhere('log_name', $like, "%{$search}%")
                  ->orWhereHasMorph('causer', [\App\Models\User::class], function($q2) use ($search, $like) {
                      $q2->where('name', $like, "%{$search}%")
                        ->orWhere('email', $like, "%{$search}%");
                  });
            });
        }
    }
}
