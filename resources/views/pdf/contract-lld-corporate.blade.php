<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ordre de prélèvement automatique — {{ $reservation->reservation_number }}</title>
    <style>
        * { margin: 0; padding: 0; }
        @page { margin: 24px 26px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #000; line-height: 1.5; }

        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        td, th { vertical-align: top; }

        .outer { border: 1px solid #000; }
        .outer + .outer { border-top: none; }

        .main-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #1a3a8f;
            letter-spacing: 0.6px;
            padding: 6px 0 2px;
        }
        .contract-ref { text-align: center; font-size: 9.5px; margin-bottom: 12px; }
        .contract-ref b { color: #c0392b; }

        .col-title {
            background: #eef1f6;
            font-weight: bold;
            font-size: 9px;
            padding: 6px 10px;
            border-bottom: 1px solid #000;
        }
        .acct-table td { border-bottom: 1px solid #ddd; padding: 4px 10px; font-size: 8.8px; }
        .acct-table .lbl { width: 34%; color: #444; }
        .acct-table .val { font-weight: bold; }
        .acct-table .half { width: 50%; }
        .acct-table .hairline { border-bottom: 1px solid #000; }

        .veh-table td { border-bottom: 1px solid #ddd; padding: 4px 10px; font-size: 8.8px; }
        .veh-table .lbl { width: 15%; color: #444; }
        .veh-table .lbl-w { width: 30%; color: #444; }
        .veh-table .val { font-weight: bold; }

        .section-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #1a3a8f;
            background: #eef1f6;
            padding: 6px 10px;
            border-bottom: 1px solid #000;
        }

        .auth-block { padding: 8px 12px; font-size: 8.6px; line-height: 1.7; text-align: justify; }
        .auth-block p { margin-bottom: 8px; }
        .auth-line { margin: 2px 0; }
        .auth-field { border-bottom: 1px dotted #777; padding: 4px 2px; font-weight: bold; min-height: 14px; }

        .echeance { border-collapse: collapse; width: 100%; }
        .echeance td { border-bottom: 1px solid #ddd; padding: 5px 12px; font-size: 8.8px; }
        .echeance .lbl { width: 40%; color: #444; }
        .echeance .val { font-weight: bold; }

        .sig-table td { padding: 12px; font-size: 8.8px; width: 50%; vertical-align: top; }
        .sig-table .sig-title { font-weight: bold; margin-bottom: 6px; }
        .sig-table .fait { margin-top: 8px; font-size: 8.6px; }
        .sig-space { height: 46px; position: relative; border-bottom: 1px solid #999; }
        .sig-img { position: absolute; left: 0; bottom: 0; max-height: 44px; max-width: 130px; }
        .stamp-img { position: absolute; right: 8px; bottom: 0; max-height: 66px; max-width: 100px; opacity: 0.85; }

        .footer-note { text-align: center; font-size: 6.5px; color: #999; padding-top: 10px; }
    </style>
</head>
<body>

<div class="main-title">ORDRE DE PRELEVEMENT AUTOMATIQUE PERMANENT ET IRREVOCABLE</div>
<div class="contract-ref">CONTRAT DE LOCATION N° : <b>{{ $reservation->reservation_number ?? '—' }}</b></div>

@php
    $client = $reservation->client;
    $vehicle = $reservation->vehicle;
    $pickup = $reservation->pickup_date;
    $lessorName = $reservation->agency?->name ?? $company['name'] ?? config('app.name');
@endphp

<table class="outer">
    <tr>
        <td class="half" style="border-right:1px solid #000;">
            <div class="col-title">Nom et adresse du titulaire du compte à débiter</div>
            <table class="acct-table">
                <tr>
                    <td class="lbl">Raison sociale :</td>
                    <td class="val">{{ $client?->company_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Adresse :</td>
                    <td class="val">{{ $client?->company_address ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Ville / Pays :</td>
                    <td class="val">{{ trim(implode(' ', array_filter([$client?->company_city, $client?->company_country]))) ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Téléphone :</td>
                    <td class="val">{{ $client?->company_phone ?? $client?->phone ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Email :</td>
                    <td class="val">{{ $client?->company_email ?? $client?->email ?? '—' }}</td>
                </tr>
                <tr class="hairline">
                    <td class="lbl"><b>Banque :</b></td>
                    <td class="val"><b>{{ $client?->bank_name ?? '—' }}</b></td>
                </tr>
                <tr>
                    <td class="lbl">Agence / Centre d'affaires :</td>
                    <td class="val">{{ $client?->bank_account_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Adresse de la banque :</td>
                    <td class="val">{{ $client?->bank_address ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">N° de compte :</td>
                    <td class="val">{{ $client?->bank_account_number ?? '—' }}</td>
                </tr>
            </table>
        </td>
        <td class="half">
            <div class="col-title">Nom et adresse du titulaire du compte à créditer</div>
            <table class="acct-table">
                <tr>
                    <td class="lbl">Raison sociale :</td>
                    <td class="val">{{ $lessorName }}</td>
                </tr>
                <tr>
                    <td class="lbl">Adresse :</td>
                    <td class="val">{{ $reservation->agency?->address ?? $company['address'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Ville / Pays :</td>
                    <td class="val">{{ trim(implode(' ', array_filter([$reservation->agency?->city ?? $company['city'] ?? null, $company['country'] ?? null]))) ?: '—' }}</td>
                </tr>
                <tr class="hairline">
                    <td class="lbl"><b>Banque :</b></td>
                    <td class="val"><b>{{ $company['bank_name'] ?? '—' }}</b></td>
                </tr>
                <tr>
                    <td class="lbl">Agence / Centre d'affaires :</td>
                    <td class="val">{{ $company['bank_branch'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Adresse de la banque :</td>
                    <td class="val">{{ $company['bank_address'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">N° de compte :</td>
                    <td class="val">{{ $company['bank_account'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">RIB :</td>
                    <td class="val">{{ $company['bank_rib'] ?? '—' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="section-title" style="margin-top:10px;">Véhicule et Kilométrage</div>
<table class="veh-table outer">
    <tr>
        <td class="lbl-w">Marque :</td>
        <td class="val" style="width:20%;">{{ $vehicle?->brand ?? '—' }}</td>
        <td class="lbl-w">Type :</td>
        <td class="val">{{ $vehicle?->model ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl-w">Matricule :</td>
        <td class="val" style="width:20%;">{{ $vehicle?->registration_number ?? '—' }}</td>
        <td class="lbl-w">Carburant :</td>
        <td class="val">{{ ucfirst(str_replace('_', ' ', $vehicle?->fuel_type ?? '—')) }}</td>
    </tr>
    <tr>
        <td class="lbl-w">Année :</td>
        <td class="val" style="width:20%;">{{ $vehicle?->year ?? '—' }}</td>
        <td class="lbl-w">État :</td>
        <td class="val">{{ $vehicle?->condition ? ucfirst(str_replace('_', ' ', $vehicle->condition)) : '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Km départ :</td>
        <td class="val">{{ $reservation->initial_mileage !== null ? number_format($reservation->initial_mileage) : '—' }}</td>
        <td class="lbl">Date départ :</td>
        <td class="val">{{ $pickup?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    @if($reservation->final_mileage !== null)
        <tr>
            <td class="lbl">Km retour :</td>
            <td class="val">{{ number_format($reservation->final_mileage) }}</td>
            <td class="lbl">Date retour :</td>
            <td class="val">{{ ($reservation->actual_return_date ?? $reservation->return_date)?->format('d/m/Y') ?? '—' }}</td>
        </tr>
    @endif
</table>

<div class="section-title" style="margin-top:10px;">Autorisation Client</div>
<div class="auth-block outer">
    <p>
        - Nous soussignés : <span class="auth-field">{{ $client?->company_name ?? '' }}</span><br>
        - Raison sociale : <span class="auth-field">{{ $client?->company_name ?? '' }}</span><br>
        - N° Registre de Commerce : <span class="auth-field">{{ $client?->company_rc ?? '' }}</span>
    </p>
    <p>
        Donnons ordre irrévocable de débiter sans autre avis notre compte ci-dessus de tous les prélèvements ordonnés par la société
        <b>{{ $lessorName }}</b> en sa faveur.
    </p>
    <p>
        Nous nous engageons, à ce que notre compte bancaire soit suffisamment approvisionné à la date de chaque échéance et ce, jusqu'au
        jour de son prélèvement effectif.
    </p>
    <p>
        Nous nous engageons à ne pas faire opposition aux prélèvements faits en faveur de la société <b>{{ $lessorName }}</b>.
    </p>
    <p>
        Il est bien entendu qu'en cas de litige sur un prélèvement, nous réglerons le différend avec l'organisme ci-dessus désigné. Vous
        n'aurez donc pas à nous aviser de l'exécution desdites opérations hors de l'extrait de compte et des avis de débit éventuels que vous
        nous adresserez.
    </p>
    <p>
        En cas de paiement différé, nous vous donnons ordre irrévocable et permanent de débiter notre compte ci-dessus des indemnités
        de retard au taux de 3% par mois ou fraction de mois qui pourront être présentées sous forme d'avis de prélèvement.
    </p>
</div>

<div class="section-title" style="margin-top:10px;">Détail des échéances</div>
<table class="echeance outer">
    <tr>
        <td class="lbl">Loyer TTC :</td>
        <td class="val">{{ number_format((float) ($reservation->monthly_rate ?? 0), 2, ',', ' ') }} dhs</td>
    </tr>
    <tr>
        <td class="lbl">1<sup>ère</sup> Échéance :</td>
        <td class="val">le 1<sup>er</sup> mois de la livraison du véhicule @if($pickup)({{ $pickup->format('d/m/Y') }})@endif</td>
    </tr>
    <tr>
        <td class="lbl">Durée du contrat :</td>
        <td class="val">{{ $reservation->total_months ?? '—' }} mois</td>
    </tr>
    <tr>
        <td class="lbl">Prélèvement mensuel</td>
        <td class="val">jusqu'au règlement par le locataire des différentes charges qui lui incombent</td>
    </tr>
</table>

<table class="sig-table outer">
    <tr>
        <td>
            <div class="sig-title">Signature autorisée du titulaire du compte</div>
            <div class="sig-space">
                @if($signatureDataUrl)
                    <img src="{{ $signatureDataUrl }}" class="sig-img" alt="Signature">
                @endif
            </div>
            <div class="fait">Fait à : {{ $reservation->pickup_location ?? '....................' }} le {{ $pickup?->format('d/m/Y') ?? '..../..../......' }}</div>
        </td>
        <td>
            <div class="sig-title">Signature autorisée et cachet de la banque</div>
            <div class="sig-space">
                @if($stampDataUrl)
                    <img src="{{ $stampDataUrl }}" class="stamp-img" alt="Cachet">
                @endif
            </div>
            <div class="fait">Fait à : {{ $reservation->pickup_location ?? '....................' }} le {{ $pickup?->format('d/m/Y') ?? '..../..../......' }}</div>
        </td>
    </tr>
</table>

<div class="footer-note">Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $lessorName }}</div>

</body>
</html>