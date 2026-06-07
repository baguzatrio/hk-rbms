<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Organizations/Index', [
            'organizations' => Organization::latest()->paginate(10)
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Organizations/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'shortname' => 'required|string|max:50',
        ]);

        Organization::create($validated);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organisasi berhasil dibuat.');
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
            'name'      => 'required|string|max:255',
            'shortname' => 'required|string|max:50',
        ]);

        $organization->update($validated);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organisasi berhasil diupdate.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organisasi berhasil dihapus.');
    }
}
