<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    /**
     * Gestion des comptes (role:admin).
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection(User::query()->with('farm:id,name')->orderBy('name')->paginate());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validated();

        $user = new User;
        // role et farm_id ne sont jamais mass-assignables : écriture explicite.
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'farm_id' => $validated['farm_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ])->save();

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();

        $user->fill(array_intersect_key($validated, array_flip(['name', 'email'])));

        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // role, farm_id et is_active : écriture explicite uniquement.
        $user->forceFill(array_intersect_key($validated, array_flip(['role', 'farm_id', 'is_active'])))->save();

        return new UserResource($user->load('farm:id,name'));
    }

    public function destroy(User $user): Response
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }
}
