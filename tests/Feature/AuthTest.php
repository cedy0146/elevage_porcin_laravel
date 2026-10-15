<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('authentifie un utilisateur et retourne un token', function () {
    $user = User::factory()->manager()->create();

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'role', 'is_active', 'farm_id'],
        ]);
});

it('refuse un mauvais mot de passe', function () {
    $user = User::factory()->technician()->create();

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'mauvais-mdp',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('refuse la connexion sans token', function () {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized();
});

it('refuse la connexion d un compte desactive', function () {
    $user = User::factory()->technician()->inactive()->create();

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('retourne l utilisateur connecte sur me', function () {
    $user = User::factory()->manager()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

it('revoque le token lors de la deconnexion', function () {
    $user = User::factory()->technician()->create();

    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/logout')
        ->assertNoContent();

    expect(DB::table('personal_access_tokens')->count())->toBe(0);

    // En test, le gard d'authentification est mis en cache entre deux requetes
    // du meme test : on le vide pour simuler le rechargement d'une vraie requete HTTP.
    auth()->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});
