<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrat de Location — {{ $reservation->reservation_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 20px 24px 34px 24px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

        .brand-footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 6.5px; color: #bbb; }

        .header { display: table; width: 100%; padding: 16px 0; border: 1px solid #1e3a5f; margin-bottom: 16px; }
        .header-left { display: table-cell; width: 65%; vertical-align: middle; padding: 0 16px; }
        .header-logo-img { max-height: 60px; max-width: 160px; }
        .header-logo-text { font-size: 20px; font-weight: bold; color: #1e3a5f; letter-spacing: 1px; }
        .header-agency { font-size: 9px; color: #555; line-height: 1.6; margin-top: 4px; }
        .header-agency strong { font-size: 12px; color: #1e3a5f; display: block; }
        .header-right { display: table-cell; width: 35%; vertical-align: middle; text-align: right; font-size: 10px; color: #333; padding: 0 16px; }
        .header-right .title { font-size: 13px; font-weight: bold; color: #1e3a5f; }

        .ref-bar { display: table; width: 100%; border: 1px solid #1e3a5f; border-top: none; margin-bottom: 16px; }
        .ref-cell { display: table-cell; padding: 6px 12px; font-size: 11px; }
        .ref-cell strong { color: #e85d04; font-size: 13px; }
        .ref-cell.status { text-align: right; }

        .section { margin-bottom: 9px; }
        .section-title { font-size: 10px; font-weight: bold; color: #fff; background: #1e3a5f; padding: 4px 10px; margin-bottom: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-body { border: 1px solid #d0d7de; border-top: none; padding: 7px 12px; }

        .grid-2 { display: table; width: 100%; }
        .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
        .col:last-child { padding-right: 0; padding-left: 8px; }
        .field { margin-bottom: 5px; }
        .field label { display: block; font-size: 8px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .field value { display: block; font-size: 10px; font-weight: 600; color: #1a1a1a; }

        .second-driver-empty { padding: 6px 12px; }
        .second-driver-cross { text-align: center; padding: 2px 0; }
        .second-driver-cross .cross-x { display: block; font-size: 20px; font-weight: bold; color: #c0392b; line-height: 1; }
        .second-driver-cross .cross-label { display: block; margin-top: 2px; font-size: 8px; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; }

        .depart-retour { width: 100%; border-collapse: collapse; }
        .depart-retour th, .depart-retour td { border: 1px solid #d0d7de; padding: 6px 10px; font-size: 9px; text-align: left; }
        .depart-retour th { background: #f0f4f8; color: #1e3a5f; text-transform: uppercase; letter-spacing: 0.5px; }
        .depart-retour td.label { color: #888; width: 18%; }

        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table tr td { padding: 6px 10px; border-bottom: 1px solid #eee; }
        .totals-table tr td:first-child { color: #555; }
        .totals-table tr td:last-child { text-align: right; font-weight: 600; }
        .totals-table .total-row td { font-size: 12px; font-weight: bold; color: #1e3a5f; border-top: 2px solid #1e3a5f; border-bottom: none; background: #f0f4f8; }

        .payment-row { display: table; width: 100%; margin-top: 8px; }
        .payment-cell { display: table-cell; font-size: 9px; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
        .badge-active { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #e0e7ff; color: #3730a3; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #fef3c7; color: #92400e; }

        .closure-box { margin-top: 8px; padding: 8px 10px; background: #f8f9fa; border-left: 3px solid #1e3a5f; font-size: 9px; color: #444; }
        .closure-box .fav { font-weight: bold; }
        .closure-box .fav.yes { color: #065f46; }
        .closure-box .fav.no { color: #991b1b; }

        .signature-section { margin-top: 14px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: center; padding: 0 20px; vertical-align: bottom; }
        .signature-stamp-img { max-height: 60px; max-width: 150px; margin-bottom: 4px; }
        .signature-line { border-top: 1px solid #888; margin: 24px 20px 6px; }
        .signature-line.has-image { margin-top: 4px; }
        .signature-label { font-size: 9px; color: #555; }

        .footer { margin-top: 12px; border-top: 1px solid #d0d7de; padding-top: 6px; text-align: center; font-size: 8px; color: #aaa; line-height: 1.5; }

        .page-break { page-break-before: always; }
        .cgl-title { text-align: center; margin-bottom: 18px; }
        .cgl-title h1 { font-size: 15px; font-weight: bold; color: #1e3a5f; text-transform: uppercase; letter-spacing: 1px; border: 1px solid #1e3a5f; border-radius: 20px; display: inline-block; padding: 8px 24px; }
        .cgl-intro { font-size: 9px; color: #555; margin-bottom: 16px; line-height: 1.5; }
        .cgl-columns { display: table; width: 100%; }
        .cgl-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 14px; }
        .cgl-col:last-child { padding-right: 0; padding-left: 14px; }
        .cgl-article { margin-bottom: 10px; }
        .cgl-article h4 { font-size: 9px; color: #1e3a5f; margin-bottom: 3px; }
        .cgl-article p { font-size: 7.5px; color: #555; line-height: 1.5; text-align: justify; }
    </style>
</head>
<body>

<div class="brand-footer">Généré par MyFleet-Control (myfleet-control.com)</div>

{{-- HEADER --}}
<div class="header">
    <div class="header-left">
        @if($logoDataUrl)
            <img src="{{ $logoDataUrl }}" class="header-logo-img" alt="Logo">
        @else
            <div class="header-logo-text">{{ $reservation->agency->name ?? config('app.name') }}</div>
        @endif
        <div class="header-agency">
            @if($reservation->agency)
                <strong>{{ $reservation->agency->name }}</strong>
                {{ $reservation->agency->address ?? '' }}<br>
                {{ $reservation->agency->phone ?? '' }} @if($reservation->agency->email) · {{ $reservation->agency->email }} @endif
            @endif
        </div>
    </div>
    <div class="header-right">
        <div class="title">Location de voiture sans chauffeur</div>
        <div style="margin-top:4px;">Contrat de location</div>
    </div>
</div>

{{-- REF BAR --}}
<div class="ref-bar">
    <div class="ref-cell">Locataire N° : <strong>{{ $reservation->reservation_number }}</strong></div>
    <div class="ref-cell status">
        Statut : <span class="badge badge-{{ $reservation->status }}">{{ ucfirst($reservation->status) }}</span>
        &nbsp; Généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</div>

{{-- PARTIES --}}
<div class="section">
    <div class="grid-2">
        <div class="col" style="padding-right: 0;">
            <div class="section-title">Locataire</div>
            <div class="section-body">
                <div class="field">
                    <label>Nom et prénom</label>
                    <value>{{ $reservation->client->full_name ?? trim(($reservation->client->first_name ?? '') . ' ' . ($reservation->client->last_name ?? '')) }}</value>
                </div>
                @if($reservation->client)
                    <div class="field">
                        <label>Adresse</label>
                        <value>{{ $reservation->client->address ?? '—' }}</value>
                    </div>
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
        <div class="col" style="padding-left: 0;">
            <div class="section-title">Véhicule</div>
            <div class="section-body">
                <div class="field">
                    <label>Marque / Modèle</label>
                    <value>{{ $reservation->vehicle->brand ?? '' }} {{ $reservation->vehicle->model ?? '' }}</value>
                </div>
                <div class="field">
                    <label>Immatriculation</label>
                    <value>{{ $reservation->vehicle->registration_number ?? $reservation->vehicle->license_plate ?? '—' }}</value>
                </div>
                <div class="field">
                    <label>Carburant</label>
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

{{-- SECOND CONDUCTEUR — always rendered so an empty box can never be filled in by hand afterwards --}}
@php $hasSecondDriver = $reservation->secondDriver || $reservation->second_driver_name; @endphp
<div class="section">
    <div class="section-title">Second conducteur agréé</div>
    <div class="section-body {{ $hasSecondDriver ? '' : 'second-driver-empty' }}">
        @if($hasSecondDriver)
            <div class="grid-2">
                <div class="col">
                    <div class="field">
                        <label>Nom et prénom</label>
                        <value>{{ $reservation->secondDriver->full_name ?? $reservation->second_driver_name }}</value>
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label>N° Permis de conduire</label>
                        <value>{{ $reservation->secondDriver->driver_license_number ?? $reservation->second_driver_license ?? '—' }}</value>
                    </div>
                </div>
                <div class="col">
                    <div class="field">
                        <label>Téléphone</label>
                        <value>{{ $reservation->secondDriver->phone ?? $reservation->second_driver_phone ?? '—' }}</value>
                    </div>
                </div>
            </div>
        @else
            <div class="second-driver-cross">
                <span class="cross-x">✕</span>
                <span class="cross-label">Aucun second conducteur agréé sur ce contrat</span>
            </div>
        @endif
    </div>
</div>

{{-- DEPART / RETOUR --}}
<div class="section">
    <div class="section-title">Période de location</div>
    <div class="section-body" style="padding: 0; border: none;">
        <table class="depart-retour">
            <tr>
                <th style="width:18%;"></th>
                <th>Départ</th>
                <th>Retour {{ $reservation->actual_return_date ? '(effectif)' : '(prévu)' }}</th>
            </tr>
            <tr>
                <td class="label">Date</td>
                <td>{{ $reservation->pickup_date->format('d/m/Y') }}</td>
                <td>{{ ($reservation->actual_return_date ?? $reservation->return_date)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Heure</td>
                <td>{{ $reservation->pickup_date->format('H:i') }}</td>
                <td>{{ ($reservation->actual_return_date ?? $reservation->return_date)->format('H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Km</td>
                <td>{{ $reservation->initial_mileage !== null ? number_format($reservation->initial_mileage) : '—' }}</td>
                <td>{{ $reservation->final_mileage !== null ? number_format($reservation->final_mileage) : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Lieu</td>
                <td>{{ $reservation->pickup_location ?? '—' }}</td>
                <td>{{ $reservation->actual_return_location ?? $reservation->return_location ?? $reservation->pickup_location ?? '—' }}</td>
            </tr>
        </table>
        @if($reservation->status === 'completed')
            <div class="closure-box">
                @if($reservation->is_favorable !== null)
                    Avis de clôture :
                    <span class="fav {{ $reservation->is_favorable ? 'yes' : 'no' }}">
                        {{ $reservation->is_favorable ? 'Favorable' : 'Non favorable' }}
                    </span><br>
                @endif
                @if($reservation->closure_comment)
                    <strong>Commentaire :</strong> {{ $reservation->closure_comment }}
                @endif
            </div>
        @endif
    </div>
</div>

{{-- FINANCIAL --}}
<div class="section">
    <div class="section-title">Facturation</div>
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
        <div class="payment-row">
            <div class="payment-cell">Mode de règlement : <strong>{{ ucfirst(str_replace('_', ' ', $reservation->payment_method ?? '—')) }}</strong></div>
            <div class="payment-cell" style="text-align:right;">Statut paiement : <strong>{{ ucfirst($reservation->payment_status ?? '—') }}</strong></div>
        </div>
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
            <strong>Signature du client</strong><br>
            {{ $reservation->client->full_name ?? '' }}
        </div>
    </div>
    <div class="signature-box">
        @if($stampDataUrl || $signatureDataUrl)
            @if($stampDataUrl)<img src="{{ $stampDataUrl }}" class="signature-stamp-img" alt="Cachet">@endif
            @if($signatureDataUrl)<img src="{{ $signatureDataUrl }}" class="signature-stamp-img" alt="Signature">@endif
            <div class="signature-line has-image"></div>
        @else
            <div class="signature-line"></div>
        @endif
        <div class="signature-label">
            <strong>Cachet &amp; signature de l'agent</strong><br>
            {{ $reservation->validator->full_name ?? $reservation->agency->name ?? '' }}
        </div>
    </div>
</div>

{{-- FOOTER --}}
<div class="footer">
    Le locataire reconnaît avoir reçu le véhicule en bon état et accepte les conditions générales de location au verso (page suivante).<br>
    Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $reservation->agency->name ?? config('app.name') }}
</div>

{{-- PAGE 2 — CONDITIONS GENERALES --}}
<div class="page-break"></div>
<div class="cgl-title"><h1>Conditions générales de location</h1></div>
<div class="cgl-intro">
    Le présent contrat a été établi et prend date comme indiqué au recto. Il engage le loueur (l'agence)
    et la personne ou société qui signe ce contrat, désignée ci-après « le locataire ».
</div>
<div class="cgl-columns">
    <div class="cgl-col">
        <div class="cgl-article">
            <h4>Art. 1 — Utilisation du véhicule</h4>
            <p>Le locataire s'engage à ne pas laisser conduire le véhicule par d'autres personnes que lui-même
            ou celles agréées par le loueur, et à n'utiliser le véhicule que pour ses besoins personnels. Il est
            interdit de participer à toute compétition, d'utiliser le véhicule à des fins illicites ou pour le
            transport de marchandises, ainsi que de sous-louer le véhicule sans l'accord du loueur.</p>
        </div>
        <div class="cgl-article">
            <h4>Art. 2 — État du véhicule</h4>
            <p>La voiture est livrée en parfait état de marche et de propreté. Elle sera rendue dans le même
            état ; à défaut, le locataire devra régler les frais de nettoyage et de remise en état. En cas de
            détérioration anormale, le locataire s'engage à en assumer le coût de réparation.</p>
        </div>
        <div class="cgl-article">
            <h4>Art. 3 — Essence et entretien</h4>
            <p>Le carburant est à la charge du locataire. Le locataire doit vérifier régulièrement les niveaux
            d'huile et d'eau ainsi que la pression des pneus, et signaler immédiatement toute anomalie constatée.</p>
        </div>
        <div class="cgl-article">
            <h4>Art. 4 — Réparations</h4>
            <p>L'usure mécanique normale est à la charge du loueur. Toute réparation nécessitée par une
            négligence du locataire sera à sa charge et exécutée par un garage agréé par le loueur.</p>
        </div>
        <div class="cgl-article">
            <h4>Art. 5 — Assurance</h4>
            <p>Le véhicule est couvert par une assurance responsabilité civile. Le locataire reste responsable
            de la franchise applicable en cas de sinistre, ainsi que de tout dommage exclu des garanties souscrites.</p>
        </div>
    </div>
    <div class="cgl-col">
        <div class="cgl-article">
            <h4>Art. 6 — Location, caution, prolongation</h4>
            <p>Les prix de location ainsi que la caution sont payables d'avance. La caution ne peut servir, en
            aucun cas, au locataire pour se libérer du montant de la location en cours. Toute prolongation doit
            être accordée au préalable par le loueur.</p>
        </div>
        <div class="cgl-article">
            <h4>Art. 7 — Rapatriement du véhicule</h4>
            <p>Le locataire s'interdit formellement d'abandonner le véhicule. En cas d'impossibilité matérielle
            de le restituer, celui-ci sera rapatrié aux frais et par les soins du loueur, la location restant due
            jusqu'au retour effectif du véhicule.</p>
        </div>
        <div class="cgl-article">
            <h4>Art. 8 — Documents du véhicule</h4>
            <p>Le locataire remettra, dès la fin de la location, la carte grise et tous les documents nécessaires
            à la circulation. En cas de perte, il devra acquitter les frais de duplicata ainsi que l'immobilisation
            du véhicule en résultant.</p>
        </div>
        <div class="cgl-article">
            <h4>Art. 9 — Responsabilité</h4>
            <p>Le locataire demeure seul responsable des amendes, contraventions et procès-verbaux établis
            contre lui pendant toute la durée de la location.</p>
        </div>
        <div class="cgl-article">
            <h4>Art. 10 — Compétence</h4>
            <p>En cas de contestation, seuls les tribunaux du ressort de l'agence loueuse sont compétents. Les
            frais de timbre et d'enregistrement restent à la charge du locataire.</p>
        </div>
    </div>
</div>

</body>
</html>
