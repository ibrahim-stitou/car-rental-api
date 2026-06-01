<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $document->type_name }} — {{ $document->document_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

        .header { display: table; width: 100%; padding-bottom: 16px; border-bottom: 2px solid #1e3a5f; margin-bottom: 20px; }
        .header-left { display: table-cell; vertical-align: top; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }
        .logo { font-size: 22px; font-weight: bold; color: #1e3a5f; letter-spacing: 1px; }
        .logo span { color: #e85d04; }
        .agency-info { font-size: 9px; color: #555; line-height: 1.6; margin-top: 4px; }
        .doc-title { font-size: 20px; font-weight: bold; color: #1e3a5f; text-transform: uppercase; letter-spacing: 2px; }
        .doc-number { font-size: 12px; color: #888; margin-top: 4px; }
        .doc-meta { font-size: 9px; color: #555; margin-top: 6px; line-height: 1.7; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-draft { background: #f3f4f6; color: #6b7280; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-paid { background: #dbeafe; color: #1e40af; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        .parties { display: table; width: 100%; margin-bottom: 20px; }
        .party { display: table-cell; width: 50%; vertical-align: top; }
        .party-box { border: 1px solid #d0d7de; padding: 12px; }
        .party:first-child .party-box { margin-right: 8px; }
        .party-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #888; margin-bottom: 6px; }
        .party-name { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-bottom: 4px; }
        .party-detail { font-size: 9px; color: #555; line-height: 1.6; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items-table thead th { background: #1e3a5f; color: #fff; padding: 7px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table thead th:last-child, .items-table thead th:nth-child(3), .items-table thead th:nth-child(2) { text-align: right; }
        .items-table tbody td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 9.5px; }
        .items-table tbody td:last-child, .items-table tbody td:nth-child(3), .items-table tbody td:nth-child(2) { text-align: right; }
        .items-table tbody tr:nth-child(even) td { background: #f8f9fa; }

        .totals { display: table; width: 100%; }
        .totals-spacer { display: table-cell; width: 55%; vertical-align: top; }
        .totals-table { display: table-cell; width: 45%; }
        .totals-row { display: table; width: 100%; }
        .totals-row-cell { display: table-cell; padding: 5px 10px; font-size: 10px; }
        .totals-row-cell:last-child { text-align: right; font-weight: 600; }
        .totals-border { border-bottom: 1px solid #eee; }
        .totals-grand { background: #1e3a5f; color: #fff; }
        .totals-grand .totals-row-cell { font-size: 12px; font-weight: bold; padding: 8px 10px; }

        .notes { margin-top: 16px; padding: 10px; background: #f8f9fa; border-left: 3px solid #1e3a5f; font-size: 9px; color: #555; line-height: 1.6; }
        .notes strong { color: #1e3a5f; }

        .payment-info { margin-top: 16px; border: 1px solid #d0d7de; padding: 12px; }
        .payment-info h4 { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #1e3a5f; margin-bottom: 8px; }
        .payment-grid { display: table; width: 100%; }
        .payment-cell { display: table-cell; width: 33%; font-size: 9px; }
        .payment-cell label { display: block; color: #888; font-size: 8px; text-transform: uppercase; margin-bottom: 2px; }
        .payment-cell value { display: block; font-weight: 600; }

        .footer { margin-top: 30px; border-top: 1px solid #d0d7de; padding-top: 8px; text-align: center; font-size: 8px; color: #aaa; }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <div class="header-left">
        <div class="logo">GES<span>CARS</span></div>
        @if($document->agency)
        <div class="agency-info">
            {{ $document->agency->name }}<br>
            {{ $document->agency->address ?? '' }}<br>
            @if($document->agency->phone) Tél : {{ $document->agency->phone }}<br>@endif
            @if($document->agency->email) {{ $document->agency->email }}<br>@endif
        </div>
        @endif
    </div>
    <div class="header-right">
        <div class="doc-title">{{ $document->type_name }}</div>
        <div class="doc-number">N° {{ $document->document_number }}</div>
        <div class="doc-meta">
            Date d'émission : {{ $document->issue_date->format('d/m/Y') }}<br>
            @if($document->due_date)Date d'échéance : {{ $document->due_date->format('d/m/Y') }}<br>@endif
            Statut : <span class="badge badge-{{ $document->status }}">{{ ucfirst($document->status) }}</span>
        </div>
    </div>
</div>

{{-- PARTIES --}}
<div class="parties">
    <div class="party">
        <div class="party-box">
            <div class="party-label">Émetteur</div>
            <div class="party-name">{{ $document->agency->name ?? config('app.name') }}</div>
            <div class="party-detail">
                @if($document->agency)
                    {{ $document->agency->address ?? '' }}<br>
                    @if($document->agency->phone) Tél : {{ $document->agency->phone }}<br>@endif
                @endif
            </div>
        </div>
    </div>
    <div class="party">
        <div class="party-box" style="margin-left:8px;">
            <div class="party-label">Destinataire</div>
            <div class="party-name">{{ $document->client_name }}</div>
            <div class="party-detail">
                @if($document->client_address){{ $document->client_address }}<br>@endif
                @if($document->client_phone)Tél : {{ $document->client_phone }}<br>@endif
                @if($document->client_email){{ $document->client_email }}<br>@endif
            </div>
        </div>
    </div>
</div>

{{-- ITEMS TABLE --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width:50%">Description</th>
            <th style="width:12%">Quantité</th>
            <th style="width:18%">Prix unitaire</th>
            <th style="width:20%">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($document->items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ number_format($item->unit_price, 2) }} MAD</td>
            <td>{{ number_format($item->total_price, 2) }} MAD</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" style="text-align:center; color:#888; padding:16px;">Aucun article</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- TOTALS --}}
<div class="totals">
    <div class="totals-spacer">
        @if($document->notes)
        <div class="notes">
            <strong>Notes :</strong> {{ $document->notes }}
        </div>
        @endif
    </div>
    <div class="totals-table">
        <div class="totals-row totals-border">
            <div class="totals-row-cell">Sous-total</div>
            <div class="totals-row-cell">{{ number_format($document->subtotal, 2) }} MAD</div>
        </div>
        @if(($document->discount_percentage ?? 0) > 0)
        <div class="totals-row totals-border">
            <div class="totals-row-cell">Remise ({{ $document->discount_percentage }}%)</div>
            <div class="totals-row-cell">- {{ number_format($document->discount_amount, 2) }} MAD</div>
        </div>
        @endif
        @if(($document->tax_rate ?? 0) > 0)
        <div class="totals-row totals-border">
            <div class="totals-row-cell">TVA ({{ $document->tax_rate }}%)</div>
            <div class="totals-row-cell">{{ number_format($document->tax_amount, 2) }} MAD</div>
        </div>
        @endif
        <div class="totals-row totals-grand">
            <div class="totals-row-cell">TOTAL</div>
            <div class="totals-row-cell">{{ number_format($document->total_amount, 2) }} MAD</div>
        </div>
        @if($document->paid_amount > 0)
        <div class="totals-row totals-border">
            <div class="totals-row-cell" style="color:#065f46;">Montant payé</div>
            <div class="totals-row-cell" style="color:#065f46;">- {{ number_format($document->paid_amount, 2) }} MAD</div>
        </div>
        <div class="totals-row">
            <div class="totals-row-cell" style="font-weight:bold;">Reste à payer</div>
            <div class="totals-row-cell" style="font-weight:bold; color:{{ $document->balance <= 0 ? '#065f46' : '#991b1b' }};">
                {{ number_format(max(0, $document->balance), 2) }} MAD
            </div>
        </div>
        @endif
    </div>
</div>

{{-- PAYMENT INFO (if paid) --}}
@if($document->status === 'paid' && $document->payment_method)
<div class="payment-info">
    <h4>Informations de paiement</h4>
    <div class="payment-grid">
        <div class="payment-cell">
            <label>Mode de paiement</label>
            <value>{{ ucfirst(str_replace('_', ' ', $document->payment_method)) }}</value>
        </div>
        @if($document->payment_reference)
        <div class="payment-cell">
            <label>Référence</label>
            <value>{{ $document->payment_reference }}</value>
        </div>
        @endif
        @if($document->paid_at)
        <div class="payment-cell">
            <label>Date de paiement</label>
            <value>{{ $document->paid_at->format('d/m/Y') }}</value>
        </div>
        @endif
    </div>
</div>
@endif

{{-- FOOTER --}}
<div class="footer">
    {{ $document->type_name }} N° {{ $document->document_number }} · {{ config('app.name') }} · Généré le {{ now()->format('d/m/Y à H:i') }}
</div>

</body>
</html>
