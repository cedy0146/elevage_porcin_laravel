<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authentifie l'utilisateur et retourne un token personnel.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            Log::warning('Echec de connexion', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides.',
            ]);
        }

        if (! $user->is_active) {
            Log::warning('Connexion refusee : compte desactive', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Votre compte est désactivé. Contactez votre administrateur.',
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Révoque le token courant.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Retourne l'utilisateur connecté.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
