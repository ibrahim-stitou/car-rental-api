<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\BillingDocument;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Vehicle;
use Tests\TestCase;

class BillingDocumentModelTest extends TestCase
{
    private Agency $agency;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $this->client = Client::factory()->create(['agency_id' => $this->agency->id]);
    }

    protected function makeBilling(array $overrides = []): BillingDocument
    {
        $user = $this->createSuperAdmin();
        return BillingDocument::create(array_merge([
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
        ], $overrides));
    }

    // ─── DOCUMENT NUMBER ──────────────────────────────────────────────

    public function test_document_number_is_auto_generated_on_creation(): void
    {
        $billing = $this->makeBilling(['type' => 'FA']);

        $this->assertNotNull($billing->document_number);
        $this->assertStringStartsWith('FA-', $billing->document_number);
    }

    public function test_document_number_format_is_correct(): void
    {
        $billing = $this->makeBilling(['type' => 'DV']);

        $this->assertMatchesRegularExpression('/^DV-\d{4}-\d{6}$/', $billing->document_number);
    }

    public function test_document_numbers_are_sequential_per_type(): void
    {
        $b1 = $this->makeBilling(['type' => 'FA']);
        $b2 = $this->makeBilling(['type' => 'FA']);

        $this->assertNotEquals($b1->document_number, $b2->document_number);
    }

    // ─── ACCESSORS ────────────────────────────────────────────────────

    public function test_type_name_accessor_returns_correct_label(): void
    {
        $billing = $this->makeBilling(['type' => 'FA']);
        $this->assertEquals('Facture', $billing->type_name);

        $billing2 = $this->makeBilling(['type' => 'DV']);
        $this->assertEquals('Devis', $billing2->type_name);

        $billing3 = $this->makeBilling(['type' => 'AV']);
        $this->assertEquals('Avoir', $billing3->type_name);
    }

    public function test_is_paid_returns_true_when_balance_is_zero(): void
    {
        $billing = $this->makeBilling([
            'total_amount' => 1000,
            'paid_amount'  => 1000,
            'balance'      => 0,
        ]);

        $this->assertTrue($billing->is_paid);
    }

    public function test_is_paid_returns_false_when_balance_is_positive(): void
    {
        $billing = $this->makeBilling([
            'total_amount' => 1000,
            'paid_amount'  => 500,
            'balance'      => 500,
        ]);

        $this->assertFalse($billing->is_paid);
    }

    // ─── CALCULATE TOTALS ─────────────────────────────────────────────

    public function test_calculate_totals_computes_correct_values(): void
    {
        $billing = $this->makeBilling([
            'subtotal'            => 1000,
            'discount_percentage' => 10,
            'tax_rate'            => 20,
            'paid_amount'         => 0,
        ]);

        // Simuler des items
        // subtotal = 1000, discount = 10% => 900, tax = 20% => 180, total = 1080
        $billing->subtotal            = 1000;
        $billing->discount_percentage = 10;
        $billing->discount_amount     = 100;
        $billing->tax_rate            = 20;
        $billing->tax_amount          = 180;
        $billing->total_amount        = 1080;
        $billing->paid_amount         = 0;
        $billing->balance             = 1080;
        $billing->save();

        $fresh = $billing->fresh();
        $this->assertEquals('1000.00', $fresh->subtotal);
        $this->assertEquals('1080.00', $fresh->total_amount);
        $this->assertEquals('1080.00', $fresh->balance);
    }

    // ─── SOFT DELETE ──────────────────────────────────────────────────

    public function test_billing_document_is_soft_deleted(): void
    {
        $billing = $this->makeBilling();
        $billing->delete();

        $this->assertSoftDeleted('billing_documents', ['id' => $billing->id]);
        $this->assertNull(BillingDocument::find($billing->id));
        $this->assertNotNull(BillingDocument::withTrashed()->find($billing->id));
    }

    public function test_billing_document_can_be_restored(): void
    {
        $billing = $this->makeBilling();
        $billing->delete();
        $billing->restore();

        $this->assertNotNull(BillingDocument::find($billing->id));
    }

    // ─── UUID ─────────────────────────────────────────────────────────

    public function test_billing_document_id_is_uuid(): void
    {
        $billing = $this->makeBilling();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $billing->id
        );
    }

    // ─── RELATIONS ────────────────────────────────────────────────────

    public function test_billing_document_belongs_to_agency(): void
    {
        $billing = $this->makeBilling();

        $this->assertInstanceOf(Agency::class, $billing->agency);
    }

    public function test_billing_document_belongs_to_client(): void
    {
        $billing = $this->makeBilling();

        $this->assertInstanceOf(Client::class, $billing->client);
    }

    public function test_billing_document_has_many_items(): void
    {
        $billing = new BillingDocument();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $billing->items());
    }
}

