<?php

namespace Tests\Feature\Billing;

use App\Models\Agency;
use App\Models\BillingDocument;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Vehicle;
use Tests\TestCase;

class BillingTest extends TestCase
{
    private Agency $agency;
    private Client $client;
    private Vehicle $vehicle;
    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency  = Agency::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $this->client  = Client::factory()->create(['agency_id' => $this->agency->id]);
        $this->reservation = Reservation::factory()->create([
            'agency_id'  => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'client_id'  => $this->client->id,
            'status'     => 'completed',
        ]);
    }

    // ─── INDEX ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_list_billing_documents(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/billing');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_unauthenticated_user_cannot_list_billing_documents(): void
    {
        $response = $this->getJson('/api/v1/billing');

        $response->assertStatus(401);
    }

    // ─── STORE ────────────────────────────────────────────────────────

    public function test_user_with_permission_can_create_billing_document(): void
    {
        $user = $this->createSuperAdmin();

        $data = [
            'type'            => 'FA',
            'agency_id'       => $this->agency->id,
            'reservation_id'  => $this->reservation->id,
            'client_id'       => $this->client->id,
            'client_name'     => 'Ahmed Benali',
            'client_address'  => '10 Rue de Test, Casablanca',
            'client_phone'    => '+212 600 111 222',
            'client_email'    => 'ahmed@example.com',
            'issue_date'      => now()->toDateString(),
            'due_date'        => now()->addDays(30)->toDateString(),
            'subtotal'        => 5000.00,
            'tax_rate'        => 20.00,
            'tax_amount'      => 1000.00,
            'total_amount'    => 6000.00,
            'paid_amount'     => 0,
            'balance'         => 6000.00,
            'payment_method'  => 'bank_transfer',
            'status'          => 'draft',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/billing', $data);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_viewer_cannot_create_billing_document(): void
    {
        $user = $this->createViewer();

        $data = [
            'type'         => 'FA',
            'agency_id'    => $this->agency->id,
            'client_id'    => $this->client->id,
            'client_name'  => 'Test',
            'issue_date'   => now()->toDateString(),
            'total_amount' => 1000.00,
            'status'       => 'draft',
        ];

        $response = $this->authAs($user)->postJson('/api/v1/billing', $data);

        $response->assertStatus(403);
    }

    public function test_create_billing_document_fails_without_required_fields(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/billing', []);

        $response->assertStatus(422);
    }

    // ─── CREATE FROM RESERVATION ──────────────────────────────────────

    public function test_can_create_billing_from_reservation(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson("/api/v1/billing/from-reservation/{$this->reservation->id}");

        $response->assertOk();
    }

    public function test_create_billing_from_nonexistent_reservation_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->postJson('/api/v1/billing/from-reservation/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_billing_document(): void
    {
        $user = $this->createSuperAdmin();
        $billing = BillingDocument::create([
            'type'         => 'FA',
            'status'       => 'draft',
            'agency_id'    => $this->agency->id,
            'client_id'    => $this->client->id,
            'client_name'  => 'Test Client',
            'issue_date'   => now(),
            'subtotal'     => 1000,
            'tax_rate'     => 20,
            'tax_amount'   => 200,
            'total_amount' => 1200,
            'paid_amount'  => 0,
            'balance'      => 1200,
            'created_by'   => $user->id,
        ]);

        $response = $this->authAs($user)->getJson("/api/v1/billing/{$billing->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_view_nonexistent_billing_document_returns_404(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/billing/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────

    public function test_user_with_permission_can_update_billing_document(): void
    {
        $user = $this->createSuperAdmin();
        $billing = BillingDocument::create([
            'type'         => 'DV',
            'status'       => 'draft',
            'agency_id'    => $this->agency->id,
            'client_id'    => $this->client->id,
            'client_name'  => 'Test Client',
            'issue_date'   => now(),
            'subtotal'     => 2000,
            'tax_rate'     => 20,
            'tax_amount'   => 400,
            'total_amount' => 2400,
            'paid_amount'  => 0,
            'balance'      => 2400,
            'created_by'   => $user->id,
        ]);

        $response = $this->authAs($user)->putJson("/api/v1/billing/{$billing->id}", [
            'notes' => 'Notes mises à jour',
        ]);

        $response->assertOk();
    }

    // ─── DELETE ───────────────────────────────────────────────────────

    public function test_user_with_permission_can_delete_billing_document(): void
    {
        $user = $this->createSuperAdmin();
        $billing = BillingDocument::create([
            'type'         => 'DV',
            'status'       => 'draft',
            'agency_id'    => $this->agency->id,
            'client_id'    => $this->client->id,
            'client_name'  => 'Test',
            'issue_date'   => now(),
            'subtotal'     => 500,
            'tax_rate'     => 20,
            'tax_amount'   => 100,
            'total_amount' => 600,
            'paid_amount'  => 0,
            'balance'      => 600,
            'created_by'   => $user->id,
        ]);

        $response = $this->authAs($user)->deleteJson("/api/v1/billing/{$billing->id}");

        $response->assertOk();
        $this->assertSoftDeleted('billing_documents', ['id' => $billing->id]);
    }

    // ─── APPROVE ──────────────────────────────────────────────────────

    public function test_user_with_permission_can_approve_billing_document(): void
    {
        $user = $this->createSuperAdmin();
        $billing = BillingDocument::create([
            'type'         => 'FA',
            'status'       => 'draft',
            'agency_id'    => $this->agency->id,
            'client_id'    => $this->client->id,
            'client_name'  => 'Test',
            'issue_date'   => now(),
            'subtotal'     => 3000,
            'tax_rate'     => 20,
            'tax_amount'   => 600,
            'total_amount' => 3600,
            'paid_amount'  => 0,
            'balance'      => 3600,
            'created_by'   => $user->id,
        ]);

        $response = $this->authAs($user)->postJson("/api/v1/billing/{$billing->id}/approve");

        $response->assertOk();
    }

    // ─── MARK AS PAID ─────────────────────────────────────────────────

    public function test_can_mark_billing_document_as_paid(): void
    {
        $user = $this->createSuperAdmin();
        $billing = BillingDocument::create([
            'type'         => 'FA',
            'status'       => 'approved',
            'agency_id'    => $this->agency->id,
            'client_id'    => $this->client->id,
            'client_name'  => 'Test',
            'issue_date'   => now(),
            'subtotal'     => 1000,
            'tax_rate'     => 20,
            'tax_amount'   => 200,
            'total_amount' => 1200,
            'paid_amount'  => 0,
            'balance'      => 1200,
            'created_by'   => $user->id,
        ]);

        $response = $this->authAs($user)->postJson("/api/v1/billing/{$billing->id}/mark-paid", [
            'payment_method'    => 'bank_transfer',
            'payment_reference' => 'REF-PAY-001',
        ]);

        $response->assertOk();
    }

    // ─── STATISTICS ───────────────────────────────────────────────────

    public function test_can_get_billing_statistics(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/billing/statistics');

        $response->assertOk();
    }

    // ─── DATATABLE ────────────────────────────────────────────────────

    public function test_can_get_billing_datatable(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->authAs($user)->getJson('/api/v1/billing/datatable');

        $response->assertOk();
    }

    // ─── RESTORE ──────────────────────────────────────────────────────

    public function test_can_restore_deleted_billing_document(): void
    {
        $user = $this->createSuperAdmin();
        $billing = BillingDocument::create([
            'type'         => 'DV',
            'status'       => 'draft',
            'agency_id'    => $this->agency->id,
            'client_id'    => $this->client->id,
            'client_name'  => 'Test',
            'issue_date'   => now(),
            'subtotal'     => 500,
            'tax_rate'     => 20,
            'tax_amount'   => 100,
            'total_amount' => 600,
            'paid_amount'  => 0,
            'balance'      => 600,
            'created_by'   => $user->id,
        ]);
        $billing->delete();

        $response = $this->authAs($user)->postJson("/api/v1/billing/{$billing->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('billing_documents', [
            'id'         => $billing->id,
            'deleted_at' => null,
        ]);
    }
}

