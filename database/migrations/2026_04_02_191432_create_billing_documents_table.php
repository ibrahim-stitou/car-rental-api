<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_number')->unique();
            $table->enum('type', ['BC', 'BR', 'BL', 'DV', 'FA', 'AV']); // Bon de Commande, Bon de Réception, Bon de Livraison, Devis, Facture, Avoir
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'paid', 'cancelled'])->default('draft');
            $table->uuid('agency_id');
            $table->uuid('reservation_id')->nullable(); // Lié à une réservation (facultatif)
            $table->uuid('client_id')->nullable(); // Client direct (si non lié à réservation)
            $table->string('client_name'); // Nom du client/entité
            $table->text('client_address')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable(); // Pour factures
            $table->date('delivery_date')->nullable(); // Pour BL
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(20); // TVA 20%
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'check', 'online'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->string('reference_document_number')->nullable(); // Référence à un autre document (ex: FA fait référence au DV)
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('restrict');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->index('type');
            $table->index('status');
            $table->index('issue_date');
            $table->index('agency_id');
            $table->index('reservation_id');
            $table->index('client_id');
        });

        Schema::create('billing_document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('billing_document_id');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('billing_document_id')->references('id')->on('billing_documents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_document_items');
        Schema::dropIfExists('billing_documents');
    }
};

