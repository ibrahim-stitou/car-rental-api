<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrat de Location — {{ $reservation->reservation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; padding: 20px 0 16px; border-bottom: 2px solid #1e3a5f; margin-bottom: 20px; }
        .header-logo { font-size: 22px; font-weight: bold; color: #1e3a5f; letter-spacing: 1px; }
        .header-logo span { color: #e85d04; }
        .header-info { text-align: right; font-size: 9px; color: #555; line-height: 1.6; }
        .header-info strong { font-size: 11px; color: #1e3a5f; display: block; }

        .contract-title { text-align: center; margin: 0 0 20px; }
        .contract-title h1 { font-size: 16px; font-weight: bold; color: #1e3a5f; text-transform: uppercase; letter-spacing: 2px; }
        .contract-title .ref { font-size: 11px; color: #888; margin-top: 4px; }

        .section { margin-bottom: 16px; }
        .section-title { font-size: 10px; font-weight: bold; color: #fff; background: #1e3a5f; padding: 5px 10px; margin-bottom: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-body { border: 1px solid #d0d7de; border-top: none; padding: 12px; }

        .grid-2 { display: table; width: 100%; }
        .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
        .col:last-child { padding-right: 0; padding-left: 8px; }
        .field { margin-bottom: 8px; }
        .field label { display: block; font-size: 8px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .field value { display: block; font-size: 10px; font-weight: 600; color: #1a1a1a; }

        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table tr td { padding: 6px 10px; border-bottom: 1px solid #eee; }
        .totals-table tr td:first-child { color: #555; }
        .totals-table tr td:last-child { text-align: right; font-weight: 600; }
        .totals-table .total-row td { font-size: 12px; font-weight: bold; color: #1e3a5f; border-top: 2px solid #1e3a5f; border-bottom: none; background: #f0f4f8; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
        .badge-active { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #e0e7ff; color: #3730a3; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #fef3c7; color: #92400e; }

        .signature-section { margin-top: 30px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: center; padding: 0 20px; }
        .signature-line { border-top: 1px solid #888; margin: 50px 20px 6px; }
        .signature-label { font-size: 9px; color: #555; }

        .terms { margin-top: 20px; border-top: 1px solid #eee; padding-top: 12px; }
        .terms h4 { font-size: 9px; color: #1e3a5f; text-transform: uppercase; margin-bottom: 6px; }
        .terms p { font-size: 8px; color: #777; line-height: 1.5; }

        .footer { margin-top: 24px; border-top: 1px solid #d0d7de; padding-top: 8px; text-align: center; font-size: 8px; color: #aaa; }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <div>
        <div class="header-logo">GES<span>CARS</span></div>
        <div style="font-size:9px; color:#555; margin-top:4px; line-height:1.6;">
            @if($reservation->agency)
                {{ $reservation->agency->name }}<br>
                {{ $reservation->agency->address ?? '' }}<br>
                {{ $reservation->agency->phone ?? '' }}<br>
                {{ $reservation->agency->email ?? '' }}
            @endif
        </div>
    </div>
    <div class="header-info">
        <strong>Contrat de Location</strong>
        Réf. : {{ $reservation->reservation_number }}<br>
        Date : {{ now()->format('d/m/Y') }}<br>
        Statut : <span class="badge badge-{{ $reservation->status }}">{{ ucfirst($reservation->status) }}</span>
    </div>
</div>

{{-- TITLE --}}
<div class="contract-title">
    <h1>Contrat de Location de Véhicule</h1>
    <div class="ref">{{ $reservation->reservation_number }} · Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>

{{-- PARTIES --}}
<div class="section">
    <div class="section-title">Parties du contrat</div>
    <div class="section-body">
        <div class="grid-2">
            <div class="col">
                <div class="field">
                    <label>Loueur (Agence)</label>
                    <value>{{ $reservation->agency->name ?? 'N/A' }}</value>
                </div>
                @if($reservation->agency)
                    <div class="field">
                        <label>Adresse</label>
                        <value>{{ $reservation->agency->address ?? '—' }}</value>
                    </div>
                    <div class="field">
                        <label>Téléphone</label>
                        <value>{{ $reservation->agency->phone ?? '—' }}</value>
                    </div>
                @endif
            </div>
            <div class="col">
                <div class="field">
                    <label>Locataire</label>
                    <value>{{ $reservation->client->full_name ?? ($reservation->client->first_name . ' ' . $reservation->client->last_name) }}</value>
                </div>
                @if($reservation->client)
                    <div class="field">
                        <label>Téléphone</label>
                        <value>{{ $reservation->client->phone ?? '—' }}</value>
                    </div>
                    <div class="field">
                        <label>N° Permis de conduire</label>
                        <value>{{ $reservation->client->driver_license_number ?? '—' }}</value>
                    </div>
                    <div class="field">
                        <label>CIN / Passeport</label>
                        <value>{{ $reservation->client->national_id ?? $reservation->client->passport_number ?? '—' }}</value>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- VEHICLE --}}
<div class="section">
    <div class="section-title">Véhicule loué</div>
    <div class="section-body">
        <div class="grid-2">
            <div class="col">
                <div class="field">
                    <label>Marque / Modèle</label>
                    <value>{{ $reservation->vehicle->brand ?? '' }} {{ $reservation->vehicle->model ?? '' }}</value>
                </div>
                <div class="field">
                    <label>Immatriculation</label>
                    <value>{{ $reservation->vehicle->license_plate ?? '—' }}</value>
                </div>
                <div class="field">
                    <label>Couleur</label>
                    <value>{{ $reservation->vehicle->color ?? '—' }}</value>
                </div>
            </div>
            <div class="col">
                <div class="field">
                    <label>Kilométrage initial</label>
                    <value>{{ number_format($reservation->initial_mileage ?? 0) }} km</value>
                </div>
                <div class="field">
                    <label>Niveau carburant (départ)</label>
                    <value>{{ ucfirst(str_replace('_', ' ', $reservation->fuel_level_pickup ?? '—')) }}</value>
                </div>
                <div class="field">
                    <label>Caution</label>
                    <value>{{ number_format($reservation->deposit_amount ?? 0, 2) }} MAD</value>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- PERIOD --}}
<div class="section">
    <div class="section-title">Période de location</div>
    <div class="section-body">
        <div class="grid-2">
            <div class="col">
                <div class="field">
                    <label>Date de départ</label>
                    <value>{{ $reservation->pickup_date->format('d/m/Y H:i') }}</value>
                </div>
                <div class="field">
                    <label>Lieu de départ</label>
                    <value>{{ $reservation->pickup_location ?? '—' }}</value>
                </div>
            </div>
            <div class="col">
                <div class="field">
                    <label>Date de retour prévue</label>
                    <value>{{ $reservation->return_date->format('d/m/Y H:i') }}</value>
                </div>
                <div class="field">
                    <label>Lieu de retour</label>
                    <value>{{ $reservation->return_location ?? $reservation->pickup_location ?? '—' }}</value>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- FINANCIAL --}}
<div class="section">
    <div class="section-title">Détails financiers</div>
    <div class="section-body">
        <table class="totals-table">
            <tr>
                <td>Tarif journalier</td>
                <td>{{ number_format($reservation->daily_rate, 2) }} MAD</td>
            </tr>
            <tr>
                <td>Nombre de jours</td>
                <td>{{ $reservation->total_days ?? '—' }}</td>
            </tr>
            <tr>
                <td>Sous-total</td>
                <td>{{ number_format($reservation->subtotal ?? 0, 2) }} MAD</td>
            </tr>
            @if(($reservation->discount_percentage ?? 0) > 0)
            <tr>
                <td>Remise ({{ $reservation->discount_percentage }}%)</td>
                <td>- {{ number_format($reservation->discount_amount ?? 0, 2) }} MAD</td>
            </tr>
            @endif
            @if(($reservation->additional_fees ?? 0) > 0)
            <tr>
                <td>Frais supplémentaires</td>
                <td>{{ number_format($reservation->additional_fees, 2) }} MAD</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL</td>
                <td>{{ number_format($reservation->total_amount ?? 0, 2) }} MAD</td>
            </tr>
        </table>
        @if($reservation->notes)
        <div style="margin-top:10px; padding:8px; background:#f8f9fa; border-left:3px solid #1e3a5f; font-size:9px; color:#555;">
            <strong>Notes :</strong> {{ $reservation->notes }}
        </div>
        @endif
    </div>
</div>

{{-- SIGNATURES --}}
<div class="signature-section">
    <div class="signature-box">
        <div class="signature-line"></div>
        <div class="signature-label">
            <strong>Le Loueur</strong><br>
            {{ $reservation->agency->name ?? '' }}
        </div>
    </div>
    <div class="signature-box">
        <div class="signature-line"></div>
        <div class="signature-label">
            <strong>Le Locataire</strong><br>
            {{ $reservation->client->full_name ?? ($reservation->client->first_name . ' ' . $reservation->client->last_name) }}
        </div>
    </div>
</div>

{{-- TERMS --}}
<div class="terms">
    <h4>Conditions générales</h4>
    <p>
        Le locataire reconnaît avoir reçu le véhicule en bon état et s'engage à le restituer dans le même état.
        Tout dommage constaté lors du retour sera facturé au locataire.
        Le locataire est responsable de toutes infractions commises pendant la durée de la location.
        En cas de retard de retour non signalé, une pénalité journalière sera appliquée.
        La caution sera restituée après vérification du véhicule.
    </p>
</div>

{{-- FOOTER --}}
<div class="footer">
    Document généré automatiquement — {{ config('app.name') }} · {{ now()->format('d/m/Y à H:i') }}
</div>

</body>
</html>
