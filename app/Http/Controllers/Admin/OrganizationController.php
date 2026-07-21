<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $search = $request->input('search');
            $perPage = $request->input('per_page', 10);

            if (!in_array($perPage, [10, 20, 30])) {
                $perPage = 10;
            }

            $organizations = Organization::query()
                ->withCount('users')
                ->when($search, function ($query, $search) {
                    $searchLower = strtolower($search);
                    $query->where(function ($q) use ($searchLower) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(shortname) LIKE ?', ["%{$searchLower}%"]);
                    });
                })
                ->latest()
                ->paginate($perPage)
                ->withQueryString();

            return response()->json([
                'organizations' => $organizations,
                'filters' => [
                    'search' => $search,
                    'per_page' => (int) $perPage,
                ],
            ]);
        }

        return Inertia::render('Admin/Organizations/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Organizations/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255|unique:organizations,name',
            'shortname' => 'required|string|max:50|unique:organizations,shortname',
        ]);

        $organization = Organization::create($validated);

        return response()->json([
            'message' => 'Organisasi berhasil dibuat.',
            'data' => $organization,
        ], 201);
    }

    public function edit(Organization $organization): Response
    {
        return Inertia::render('Admin/Organizations/Edit', [
            'organization' => $organization
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255|unique:organizations,name,' . $organization->id,
            'shortname' => 'required|string|max:50|unique:organizations,shortname,' . $organization->id,
        ]);

        $organization->update($validated);

        return response()->json([
            'message' => 'Organisasi berhasil diupdate.',
            'data' => $organization,
        ], 200);
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();

        return response()->json([
            'message' => 'Organisasi berhasil dihapus.',
        ], 200);
    }
}
