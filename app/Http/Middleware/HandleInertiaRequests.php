<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? $this->buildUserData($request->user()) : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    private function buildUserData(\App\Models\User $user): array
    {
        // Eager load relasi yang dibutuhkan sekali saja
        if (method_exists($user, 'organization')) {
            $user->loadMissing('organization');
        }

        // Ambil role dari Spatie (fallback ke kolom 'role' jika Spatie belum assign)
        $role = $user->getRoleNames()->first() ?? $user->role ?? 'user';

        // Hitung pending booking — akan aktif setelah Fase 2 membuat model Book
        // Untuk sekarang, tetap 0 sampai model Book tersedia
        $pendingBookingCount = 0;
        if ($role === 'admin' && class_exists(\App\Models\Book::class)) {
            $pendingBookingCount = \Illuminate\Support\Facades\Cache::remember(
                'pending_bookings_count',
                60,
                fn () => \App\Models\Book::pending()->count()
            );
        }

        return [
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'role'                => $role,
            'organization'        => $user->organization ? [
                'id'        => $user->organization->id,
                'name'      => $user->organization->name,
                'shortname' => $user->organization->shortname,
            ] : null,
            'pendingCount'        => $pendingBookingCount,
            'pendingBookingCount' => $pendingBookingCount,
            'email_verified_at'   => $user->email_verified_at,
        ];
    }
}
