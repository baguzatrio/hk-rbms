<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = $request->integer('per_page', 10);

        $users = User::with('organization')
            ->when($search, function ($q, $search) {
                $searchLower = strtolower($search);
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereHas('organization', fn($o) => $o->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]));
            })
            ->latest()
            ->paginate($perPage);

        return $this->successResponse(
            UserResource::collection($users),
            'Users list retrieved.',
            200,
            [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ]
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),
            'role'            => $validated['role'],
            'organization_id' => $validated['organization_id'],
        ]);

        $user->syncRoles([$validated['role']]);

        return $this->successResponse(new UserResource($user->load('organization')), 'User berhasil dibuat.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->successResponse(new UserResource($user->load('organization')));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $user->update([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'role'            => $validated['role'],
            'organization_id' => $validated['organization_id'],
        ]);

        $user->syncRoles([$validated['role']]);

        return $this->successResponse(new UserResource($user->fresh('organization')), 'User berhasil diperbarui.');
    }

    public function destroy(User $user, Request $request): JsonResponse
    {
        // Admin self-delete protection
        if ($request->user()->id === $user->id) {
            return $this->errorResponse('Anda tidak dapat menghapus akun Anda sendiri.', 422);
        }

        $user->delete();

        return $this->successResponse(null, 'User berhasil dihapus.');
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->successResponse(null, 'Password user berhasil di-reset.');
    }
}
