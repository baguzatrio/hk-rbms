<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Traits\ApiResponse;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $organizations = Organization::latest()->get();

        return $this->successResponse(OrganizationResource::collection($organizations));
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $org = Organization::create($request->validated());

        return $this->successResponse(new OrganizationResource($org), 'Divisi berhasil ditambahkan.', 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        return $this->successResponse(new OrganizationResource($organization));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $organization->update($request->validated());

        return $this->successResponse(new OrganizationResource($organization), 'Data divisi berhasil diperbarui.');
    }

    public function destroy(Organization $organization): JsonResponse
    {
        if ($organization->users()->exists()) {
            return $this->errorResponse('Tidak dapat menghapus divisi yang masih memiliki anggota user.', 422);
        }

        $organization->delete();

        return $this->successResponse(null, 'Divisi berhasil dihapus.');
    }
}
