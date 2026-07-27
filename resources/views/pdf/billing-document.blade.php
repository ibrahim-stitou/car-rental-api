<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $document->type_name }} — {{ $document->document_number }}</title>
    <style>
        html, body { margin: 0; padding: 0; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size:   10px;
            color:       #1a1a1a;
            background:  #ffffff;
        }

        /* ── Header ─────────────────────────────────────────────────── */
        table.hdr {
            width:         100%;
            border:        none;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.hdr td { border: none; padding: 0; vertical-align: top; }
        td.hdr-l { width: 42%; }
        td.hdr-r { width: 58%; text-align: right; }

        .agency-logo    { max-height: 64px; max-width: 190px; margin-bottom: 5px; }
        .doc-type-title {
            display: block; font-size: 16px; font-weight: bold;
            text-decoration: underline; color: #1e3a5f; margin-top: 4px;
        }
        .agency-name { font-size: 12px; font-weight: bold; color: #1e3a5f; }
        .agency-sub  { font-size: 8px; color: #666; margin-top: 3px; line-height: 1.7; }

        /* ── Divider ─────────────────────────────────────────────────── */
        .divider { border: none; border-top: 2px solid #1e3a5f; margin-bottom: 16px; }

        /* ── Info block ──────────────────────────────────────────────── */
        table.info {
            width: 100%; border-collapse: collapse; margin-bottom: 18px;
        }
        table.info td {
            width: 50%; vertical-align: top;
            padding: 11px 14px; border: 1px solid #d0d7de;
        }
        td.info-r { border-left: none; }

        .lbl  { font-size: 7px; font-weight: bold; color: #999; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.3px; }
        .name { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-bottom: 2px; }
        .row  { font-size: 9px; color: #444; line-height: 1.8; }
        .key  { color: #888; font-size: 8px; }
        .val-big { font-size: 13px; font-weight: bold; color: #1e3a5f; }

        /* ── Items table ─────────────────────────────────────────────── */
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead th {
            background: #1e3a5f; color: #fff;
            padding: 8px 12px; font-size: 8px; font-weight: bold;
            text-align: left; border: none;
        }
        table.items thead th.r { text-align: right; }
        table.items thead th.c { text-align: center; }
        table.items tbody td {
            padding: 8px 12px; border-bottom: 1px solid #e8eaed;
            font-size: 9px; vertical-align: middle;
        }
        table.items tbody td.r { text-align: right; }
        table.items tbody td.c { text-align: center; }
        table.items tbody tr.even td { background: #f8f9fb; }

        /* ── Totals ──────────────────────────────────────────────────── */
        table.tot-out {
            width: 100%; border-top: 2px solid #e8eaed; border-collapse: collapse;
        }
        table.tot-out td { border: none; vertical-align: top; padding: 0; }
        td.tot-sp { width: 55%; }
        td.tot-bl { width: 45%; }

        table.tot-in { width: 100%; border-collapse: collapse; }
        table.tot-in td {
            padding: 6px 12px; font-size: 10px;
            border-bottom: 1px solid #e8eaed; vertical-align: middle;
        }
        table.tot-in td.r { text-align: right; font-weight: bold; }
        tr.grand td {
            background: #1e3a5f; color: #fff;
            font-size: 11px; font-weight: bold;
            padding: 9px 12px; border-bottom: none;
        }
        tr.grand td.r { text-align: right; }
        .c-green { color: #065f46; }
        .c-bold  { font-weight: bold; }

        /* ── Montant en lettres ──────────────────────────────────────── */
        .words {
            margin-top: 16px; padding: 9px 14px;
            background: #f0f4f8; border-left: 3px solid #1e3a5f;
            font-size: 9px; font-style: italic; color: #333;
        }
        .words strong { font-style: normal; color: #1e3a5f; }

        /* ── Box paiement ────────────────────────────────────────────── */
        .pay-box { margin-top: 16px; border: 1px solid #d0d7de; padding: 11px 14px; }
        .pay-box h4 { font-size: 8px; font-weight: bold; color: #1e3a5f; margin: 0 0 7px; }
        table.pay { width: 100%; border-collapse: collapse; }
        table.pay td { width: 33%; vertical-align: top; padding: 0 6px 0 0; border: none; font-size: 9px; }
        .pay-lbl { display: block; font-size: 7px; color: #888; margin-bottom: 2px; text-transform: uppercase; }
        .pay-val { font-weight: bold; }

        /* ── Footer fixe (position:fixed = répété sur chaque page en DOMPDF v3) ─ */
        .pdf-footer {
            position: fixed;
            bottom: 0;
            left:   0;
            right:  0;
            height: 18mm;
            border-top: 0.5mm solid #c8cdd3;
            padding-top: 3mm;
            text-align: center;
            font-size: 7.5px;
            color: #555;
            line-height: 1.7;
        }
        .footer-bold { font-weight: bold; margin-bottom: 1px; }
    </style>
</head>
<body>

@php
    $fName   = $company['name']         ?? config('app.name');
    $fType   = $company['company_type'] ?? null;
    $fCap    = $company['capital']      ?? null;
    $fRc     = $company['rc']           ?? null;
    $fIf     = $company['if']           ?? null;
    $fPatent = $company['patent']       ?? null;
    $fIce    = $company['ice']          ?? null;

    $fLine1 = $fName
        . ($fType ? ' — ' . $fType : '')
        . ($fCap  ? ' · Capital : ' . number_format((float)$fCap, 2, ',', ' ') . ' MAD' : '');

    $fParts = array_values(array_filter([
        $fRc     ? 'RC : '      . $fRc     : null,
        $fIf     ? 'IF : '      . $fIf     : null,
        $fPatent ? 'Patente : ' . $fPatent : null,
        $fIce    ? 'ICE : '     . $fIce    : null,
    ]));
    $fLine2 = implode('   |   ', $fParts);
@endphp

{{-- Footer fixe : DOMPDF v3 deep_copy ce div sur chaque nouvelle page (FrameReflower/Page.php) --}}
<div class="pdf-footer">
    <div class="footer-bold">{{ $fLine1 }}</div>
    @if($fLine2)<div>{{ $fLine2 }}</div>@endif
</div>

{{-- ════ CONTENU ═══════════════════════════════════════════════════════════ --}}
{{-- Padding simule les marges de page (@page ne fonctionne pas via Blade) --}}
{{-- padding-bottom >= hauteur footer (18mm) pour éviter le chevauchement  --}}
<div style="padding-top: 28mm; padding-right: 18mm; padding-bottom: 22mm; padding-left: 18mm;">

    {{-- En-tête --}}
    <table class="hdr" cellspacing="0" cellpadding="0">
        <tr>
            <td class="hdr-l">
                @if($logoDataUrl)
                    <img src="{{ $logoDataUrl }}" class="agency-logo" alt="logo"><br>
                @endif
                <span class="doc-type-title">{{ $document->type_name }}</span>
            </td>
            <td class="hdr-r">
                <div class="agency-name">{{ $document->agency->name ?? config('app.name') }}</div>
                @if($document->agency)
                    <div class="agency-sub">
                        @if($document->agency->address){{ $document->agency->address }}<br>@endif
                        @if($document->agency->phone)Tél : {{ $document->agency->phone }}<br>@endif
                        @if($document->agency->email){{ $document->agency->email }}@endif
                    </div>
                @endif
            </td>
        </tr>
    </table>
    <hr class="divider">

    {{-- Bloc client / référence --}}
    <table class="info" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div class="lbl">Client / Destinataire</div>
                <div class="name">{{ $document->client_name }}</div>
                @if($document->client_ice)
                    <div class="row"><span class="key">ICE :</span> {{ $document->client_ice }}</div>
                @endif
                @if($document->client_address)
                    <div class="row">{{ $document->client_address }}</div>
                @endif
                @if($document->client_phone)
                    <div class="row"><span class="key">Tél :</span> {{ $document->client_phone }}</div>
                @endif
            </td>
            <td class="info-r">
                <div class="lbl">Référence du document</div>
                <div class="row" style="margin-bottom:4px;">
                    <span class="key">N° :</span>
                    <span class="val-big">{{ $document->document_number }}</span>
                </div>
                <div class="row"><span class="key">Date :</span> {{ $document->issue_date->format('d/m/Y') }}</div>
                @if($document->due_date)
                    <div class="row"><span class="key">Échéance :</span> {{ $document->due_date->format('d/m/Y') }}</div>
                @endif
                @if($document->delivery_date)
                    <div class="row"><span class="key">Livraison :</span> {{ $document->delivery_date->format('d/m/Y') }}</div>
                @endif
                @if($document->reservation)
                    <div class="row"><span class="key">Réservation :</span> {{ $document->reservation->reservation_number }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Tableau des articles --}}
    <table class="items" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th class="c" style="width:6%">Réf</th>
                <th style="width:40%">Désignation</th>
                <th class="c" style="width:18%">Unité</th>
                <th class="r" style="width:18%">P.U. HT</th>
                <th class="r" style="width:18%">P.U. TTC</th>
            </tr>
        </thead>
        <tbody>
            @forelse($document->items as $loop_item)
                <tr class="{{ $loop->even ? 'even' : 'odd' }}">
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>{{ $loop_item->description }}</td>
                    <td class="c">{{ $loop_item->quantity }}{{ $loop_item->unit ? ' ' . $loop_item->unit : '' }}</td>
                    <td class="r">{{ number_format((float)$loop_item->unit_price, 2, ',', ' ') }}</td>
                    <td class="r">{{ number_format((float)$loop_item->unit_price * (1 + (float)$loop_item->tax_rate / 100), 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#888; padding:14px;">Aucun article</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totaux --}}
    <table class="tot-out" cellspacing="0" cellpadding="0">
        <tr>
            <td class="tot-sp"></td>
            <td class="tot-bl">
                <table class="tot-in" cellspacing="0" cellpadding="0">
                    <tr>
                        <td>Montant HT</td>
                        <td class="r">{{ number_format((float)$document->subtotal, 2, ',', ' ') }} MAD</td>
                    </tr>
                    @foreach($tvaByRate as $rate => $amount)
                        <tr>
                            <td>TVA {{ $rate }}%</td>
                            <td class="r">{{ number_format($amount, 2, ',', ' ') }} MAD</td>
                        </tr>
                    @endforeach
                    @if(empty($tvaByRate))
                        <tr><td>TVA</td><td class="r">0,00 MAD</td></tr>
                    @endif
                    <tr class="grand">
                        <td>Montant TTC</td>
                        <td class="r">{{ number_format((float)$document->total_amount, 2, ',', ' ') }} MAD</td>
                    </tr>
                    @if($document->paid_amount > 0)
                        <tr>
                            <td class="c-green">Montant payé</td>
                            <td class="r c-green">-{{ number_format((float)$document->paid_amount, 2, ',', ' ') }} MAD</td>
                        </tr>
                        <tr>
                            <td class="c-bold">Reste à payer</td>
                            <td class="r c-bold" style="color:{{ $document->balance <= 0 ? '#065f46' : '#991b1b' }};">
                                {{ number_format(max(0, (float)$document->balance), 2, ',', ' ') }} MAD
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Montant en lettres --}}
    <div class="words">
        <strong>{{ $typePhrase }} à la somme de :</strong>
        {{ ucfirst($totalInWords) }} Dirhams TTC.
    </div>

    {{-- Paiement (si payé) --}}
    @if($document->status === 'paid' && $document->payment_method)
        <div class="pay-box">
            <h4>Informations de paiement</h4>
            <table class="pay" cellspacing="0" cellpadding="0">
                <tr>
                    <td>
                        <span class="pay-lbl">Mode de paiement</span>
                        <span class="pay-val">{{ ucfirst(str_replace('_', ' ', $document->payment_method)) }}</span>
                    </td>
                    @if($document->payment_reference)
                        <td>
                            <span class="pay-lbl">Référence</span>
                            <span class="pay-val">{{ $document->payment_reference }}</span>
                        </td>
                    @endif
                    @if($document->paid_at)
                        <td>
                            <span class="pay-lbl">Date de paiement</span>
                            <span class="pay-val">{{ $document->paid_at->format('d/m/Y') }}</span>
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    @endif

</div>

</body>
</html>