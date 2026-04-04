<?php

namespace Tests\Feature\User;

use App\Models\User;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_own_profile(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/profile');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'first_name', 'last_name', 'email'],
            ]);
    }

    public function test_unauthenticated_user_cannot_view_profile(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_own_profile(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson('/api/v1/profile', [
            'first_name' => 'NouveauPrenom',
            'last_name'  => 'NouveauNom',
            'phone'      => '+212 600 999 000',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id'         => $user->id,
            'first_name' => 'NouveauPrenom',
        ]);
    }

    public function test_profile_update_fails_with_invalid_email(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->putJson('/api/v1/profile', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
    }

    public function test_profile_update_fails_with_duplicate_email(): void
    {
        $user  = $this->createSuperAdmin();
        $other = User::factory()->create();

        $response = $this->authAs($user)->putJson('/api/v1/profile', [
            'email' => $other->email,
        ]);

        $response->assertStatus(422);
    }

    // ─── CHANGE PASSWORD ──────────────────────────────────────────────

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword123']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password'      => 'oldpassword123',
                'password'              => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'correctpassword']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password'      => 'wrongpassword',
                'password'              => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response->assertStatus(422);
    }

    public function test_change_password_fails_without_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'mypassword123']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'mypassword123',
                'password'         => 'newpassword456',
            ]);

        $response->assertStatus(422);
    }

    public function test_change_password_fails_with_short_password(): void
    {
        $user = User::factory()->create(['password' => 'mypassword123']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password'      => 'mypassword123',
                'password'              => 'short',
                'password_confirmation' => 'short',
            ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_change_password(): void
    {
        $response = $this->putJson('/api/v1/profile/password', [
            'current_password'      => 'anything',
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertStatus(401);
    }
}

