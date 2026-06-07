<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logClass = 'App\Models\Log';
        $logs = collect();
        if (class_exists($logClass)) {
            $logs = $logClass::with('user')
                ->latest()
                ->paginate(20)
                ->through(fn($l) => [
                    'id'          => $l->id,
                    'action'      => $l->action,
                    'model_type'  => class_basename($l->model_type),
                    'model_id'    => $l->model_id,
                    'description' => $l->description,
                    'ip_address'  => $l->ip_address,
                    'created_at'  => $l->created_at->format('d M Y H:i'),
                    'user'        => ['id' => $l->user->id, 'name' => $l->user->name],
                ]);
        } else {
            $logs = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'user_id', 'search']),
        ]);
    }
}
