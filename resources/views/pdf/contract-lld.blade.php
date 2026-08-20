<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contrat de Location Longue Durée — {{ $reservation->reservation_number }}</title>
    <style>
        {{-- Kept byte-for-byte identical to pdf/contract.blade.php's stylesheet
             on purpose — this file exists so the LLD contract's *content* can
             diverge (billing section, a couple of clauses) without touching
             the short-term contract template, while both still look like the
             same document family. If you want LLD to look visually different
             in the future, this is the file to change. --}}
        @font-face { font-family: 'Amiri'; src: url('{{ $arabicFontRegular }}') format('truetype'); font-weight: normal; }
        @font-face { font-family: 'Amiri'; src: url('{{ $arabicFontBold }}') format('truetype'); font-weight: bold; }

        * { margin: 0; padding: 0; }
        @page { margin: 18px 22px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.4px; color: #000; }
        .page-pad { padding: 0 6px; }

        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        td, th { vertical-align: top; }

        .page-frame { width: 100%; height: 1070px; }
        .page-frame td { padding: 0; }

        .ar {
            font-family: 'Amiri', DejaVu Sans, sans-serif;
            text-align: right;
            font-size: 9px;
            white-space: nowrap;
        }

        .outer { border: 1px solid #000; border-bottom: none; }
        .outer:last-of-type { border-bottom: 1px solid #000; }

        /* ── header ───────────────────────────────────────────── */
        .header-table td { padding: 12px 18px; }
        .header-left { width: 66%; }
        .header-logo-img { max-height: 120px; max-width: 350px; display: block; margin-bottom: 6px; }
        .header-logo-text { font-size: 29px; font-weight: bold; color: #1a3a8f; letter-spacing: 0.5px; }
        .header-agency-lines { font-size: 10px; color: #222; line-height: 1.7; margin-top: 4px; }
        .header-right {
            width: 34%;
            text-align: center;
            font-size: 17px;
            font-style: italic;
            font-weight: 600;
            color: #1a3a8f;
            padding: 12px 14px 16px;
            vertical-align: bottom;
        }

        /* ── ref bar ──────────────────────────────────────────── */
        .refbar-table td { padding: 8px 16px; font-size: 9.5px; }
        .refbar-left { width: 50%; border-right: 1px solid #000; }
        .refbar-num { color: #c0392b; font-weight: bold; }

        /* ── main split ───────────────────────────────────────── */
        .split-table td { padding: 0; }
        .split-left { width: 50%; border-right: 1px solid #000; vertical-align: top; }
        .split-right { width: 50%; vertical-align: top; }

        .frow-table td { padding: 6px 12px; border-bottom: 1px solid #ddd; font-size: 8.4px; }
        .frow-label { width: 40%; white-space: nowrap; }
        .frow-value { width: 34%; font-weight: bold; }
        .frow-ar { width: 26%; }

        .subhead { background: #eef1f6; font-weight: bold; padding: 6px 12px; border-bottom: 1px solid #000; border-top: 1px solid #000; border-left: 3px solid #1a3a8f; font-size: 8.6px; }
        .subhead-ar { float: right; }

        .empty-block { padding: 10px 8px; text-align: center; }
        .empty-block .x { font-size: 16px; font-weight: bold; color: #b02a2a; }
        .empty-block .lbl { display: block; font-size: 7.5px; color: #8a2020; margin-top: 2px; }

        .legal-block { padding: 12px; text-align: center; font-size: 7.6px; line-height: 1.65; border-bottom: 1px solid #ddd; }
        .legal-block .ar { font-size: 8px; }

        .sig-row-table td { padding: 8px 12px; border-bottom: 1px solid #ddd; font-size: 8.4px; }
        .sig-row-table tr.no-border td { border-bottom: none; }
        .sig-row-line { border-bottom: 1px solid #999; height: 16px; }
        .sig-row-line.tall { height: 50px; }
        .sig-row-line.with-assets { position: relative; height: 90px; border-bottom: none; }
        .sig-img { position: absolute; left: 0; top: 0; max-height: 42px; max-width: 130px; }
        .stamp-img { position: absolute; left: 45px; top: 16px; max-height: 72px; max-width: 108px; opacity: 0.85; }

        /* ── vehicle top rows ─────────────────────────────────── */
        .veh-two-table td { padding: 6px 12px; border-bottom: 1px solid #ddd; width: 50%; font-size: 8.4px; }
        .veh-two-table .ar-sub { font-size: 7px; color: #555; }

        /* ── depart/retour ────────────────────────────────────── */
        .dr-table { border-top: 1px solid #000; }
        .dr-table td, .dr-table th { border: 1px solid #999; padding: 6px 10px; font-size: 8px; text-align: center; }
        .dr-table th { background: #eef1f6; font-weight: bold; }
        .dr-table td.lbl { text-align: left; color: #444; width: 20%; }
        .dr-ar { font-size: 7.4px; font-weight: normal; }

        /* ── facturation ──────────────────────────────────────── */
        .fact-table td { padding: 6px 12px; border-bottom: 1px solid #ddd; font-size: 8.4px; }
        .fact-table .fl { width: 46%; }
        .fact-table .fv { width: 34%; }
        .fact-table .fa { width: 20%; }

        /* ── etat vehicule ────────────────────────────────────── */
        .etat-wrap { padding: 10px 12px; border-bottom: 1px solid #ddd; }
        .chk { display: inline-block; width: 9px; height: 9px; border: 1px solid #333; text-align: center; line-height: 8px; font-size: 8px; margin-right: 3px; }
        .chk.on::after { content: "X"; }
        .car-diagram-table td { padding: 4px; text-align: center; vertical-align: middle; }
        .car-box { border: 1px solid #999; height: 34px; }
        .fuel-gauge { border: 1px solid #999; border-radius: 50%; width: 34px; height: 34px; margin: 0 auto; text-align: center; line-height: 34px; font-size: 6px; color: #888; }

        .modereg-table td { padding: 6px 12px; border-bottom: 1px solid #ddd; font-size: 8.4px; }

        .footer-note { text-align: center; font-size: 6.5px; color: #999; padding: 8px; }

        /* ── page 2 ───────────────────────────────────────────── */
        .page-break { page-break-before: always; }
        .cgl-frame { border: 1px solid #000; border-radius: 4px; padding: 12px 16px; }
        .cgl-title { text-align: center; margin: 10px 0 18px; }
        .cgl-title h1 { font-size: 14px; font-weight: bold; border: 1.5px solid #1a3a8f; color: #1a3a8f; border-radius: 20px; display: inline-block; padding: 8px 28px; letter-spacing: 0.5px; }
        .cgl-intro { font-size: 8.6px; margin-bottom: 18px; line-height: 1.6; padding-bottom: 10px; border-bottom: 2px solid #1a3a8f; }
        .cgl-columns td { width: 50%; vertical-align: top; padding: 0 14px; }
        .cgl-article { margin-bottom: 12px; }
        .cgl-article h4 { font-size: 8.8px; font-weight: bold; color: #1a3a8f; margin-bottom: 4px; }
        .cgl-article p { font-size: 7.8px; line-height: 1.55; text-align: justify; }
    </style>
</head>
<body>

{{-- ══════════════════════════ PAGE 1 ══════════════════════════ --}}
<div class="page-pad">
    <table class="page-frame">
        <tr><td>

                <table class="header-table outer">
                    <tr>
                        <td class="header-left">
                            @if($logoDataUrl)
                                <img src="{{ $logoDataUrl }}" class="header-logo-img" alt="Logo">
                            @else
                                <div class="header-logo-text">{{ $reservation->agency->name ?? $company['name'] ?? config('app.name') }}</div>
                            @endif
                            <div class="header-agency-lines">
                                @if($reservation->agency?->address ?? $company['address'] ?? null)
                                    {{ $reservation->agency->address ?? $company['address'] }}<br>
                                @endif
                                @if($reservation->agency?->phone ?? $company['phone'] ?? null)
                                    Tél/Fax: {{ $reservation->agency->phone ?? $company['phone'] }}<br>
                                @endif
                                @if($reservation->agency?->phone2 ?? $company['phone2'] ?? null)
                                    GSM: {{ $reservation->agency->phone2 ?? $company['phone2'] }}<br>
                                @endif
                                @if($reservation->agency?->email ?? $company['email'] ?? null)
                                    E.mail: {{ $reservation->agency->email ?? $company['email'] }}
                                @endif
                            </div>
                        </td>
                        <td class="header-right">
                            Location Longue Durée<br>sans chauffeur
                        </td>
                    </tr>
                </table>

                <table class="refbar-table outer">
                    <tr>
                        <td class="refbar-left"><b>LOCATAIRE -N°:</b> <span class="refbar-num">{{ $reservation->reservation_number }}</span></td>
                        <td><b>VEHICULE</b></td>
                    </tr>
                </table>

                @php
                    $client = $reservation->client;
                    $vehicle = $reservation->vehicle;
                    $second = $reservation->secondDriver;
                    $hasSecond = $second || $reservation->second_driver_name;
                    $pickup = $reservation->pickup_date;
                    $returnPlanned = $reservation->return_date;
                    $returnActual = $reservation->actual_return_date;
                    $payMethod = $reservation->payment_method;
                @endphp

                <table class="split-table outer">
                    <tr>
                        {{-- ═══ LEFT: LOCATAIRE ═══ --}}
                        <td class="split-left">

                            <table class="frow-table">
                                <tr>
                                    <td class="frow-label">Nom :</td>
                                    <td class="frow-value">{{ $client?->last_name ?? '—' }}</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('الاسم العائلي :') }}</td>
                                </tr>
                                <tr>
                                    <td class="frow-label">Prénom :</td>
                                    <td class="frow-value">{{ $client?->first_name ?? '—' }}</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('الاسم الشخصي :') }}</td>
                                </tr>
                                <tr>
                                    <td class="frow-label">Adresse :</td>
                                    <td class="frow-value">{{ $client?->address ?? '—' }}</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('العنوان :') }}</td>
                                </tr>
                                <tr>
                                    <td class="frow-label">Tél :</td>
                                    <td class="frow-value">{{ $client?->phone ?? '—' }}</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('الهاتف :') }}</td>
                                </tr>
                                <tr>
                                    <td class="frow-label">Permis de Conduire N° :</td>
                                    <td class="frow-value">{{ $client?->driving_license_number ?? '—' }}</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('رخصة رقم :') }}</td>
                                </tr>
                                <tr>
                                    <td class="frow-label">Délivré le :</td>
                                    <td class="frow-value">{{ $client?->license_issue_date?->format('d/m/Y') ?? '—' }} @if($client?->license_issue_place) à {{ $client->license_issue_place }} @endif</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('منح في :') }}</td>
                                </tr>
                                <tr>
                                    <td class="frow-label">Date et Lieu de Naissance :</td>
                                    <td class="frow-value">{{ $client?->date_of_birth?->format('d/m/Y') ?? '—' }} @if($client?->birth_place) à {{ $client->birth_place }} @endif</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('تاريخ ومكان الازدياد :') }}</td>
                                </tr>
                                <tr>
                                    <td class="frow-label">N° Passeport/CIN :</td>
                                    <td class="frow-value">{{ $client?->id_number ?? '—' }}</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('رقم جواز السفر / بطاقة التعريف :') }}</td>
                                </tr>
                                <tr>
                                    <td class="frow-label">Autre Conducteur Agréé :</td>
                                    <td class="frow-value">{{ $second ? trim($second->last_name . ' ' . $second->first_name) : ($reservation->second_driver_name ?? '—') }}</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('السائق المعتمد الثاني :') }}</td>
                                </tr>
                                @if($second)
                                    <tr>
                                        <td class="frow-label">Date et Lieu de Naissance :</td>
                                        <td class="frow-value">{{ $second->date_of_birth?->format('d/m/Y') ?? '—' }} @if($second->birth_place) à {{ $second->birth_place }} @endif</td>
                                        <td class="frow-ar ar">{{ \App\Services\PdfService::ar('تاريخ ومكان الازدياد :') }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="frow-label">Permis de Conduire N° :</td>
                                    <td class="frow-value">{{ $second?->driving_license_number ?? $reservation->second_driver_license ?? '—' }}</td>
                                    <td class="frow-ar ar">{{ \App\Services\PdfService::ar('رخصة رقم :') }}</td>
                                </tr>
                                <tr>
                                    <td class="frow-label">Observations et Réserve :</td>
                                    <td class="frow-value" colspan="2" style="font-weight:normal;">{{ $reservation->notes ?? '—' }}</td>
                                </tr>
                            </table>

                            <div class="legal-block">
                                Le Client est seul responsable des délits et contraventions des circulations.<br>
                                <span class="ar">{{ \App\Services\PdfService::ar('الزبون هو المسؤول الوحيد عن أي حادثة أو غرامة مالية عن أي مخالفة مرتكبة.') }}</span><br>
                                J'ai lu et accepte les conditions stipulées ci-contre et au verso<br>
                                <span class="ar">{{ \App\Services\PdfService::ar('اطلعت و وافقت على جميع الشروط المذكورة أعلاه وخلف العقد') }}</span>
                            </div>

                            <table class="sig-row-table">
                                <tr>
                                    <td style="width:60%;">Signature de client :<div class="sig-row-line tall"></div></td>
                                    <td class="ar" style="width:40%;">{{ \App\Services\PdfService::ar('توقيع الزبون :') }}</td>
                                </tr>
                                <tr class="{{ ($signatureDataUrl || $stampDataUrl) ? 'no-border' : '' }}">
                                    <td>Signature de l'agent :
                                        <div class="sig-row-line {{ ($signatureDataUrl || $stampDataUrl) ? 'with-assets' : '' }}">
                                            @if($signatureDataUrl)
                                                <img src="{{ $signatureDataUrl }}" class="sig-img" alt="Signature">
                                            @endif
                                            @if($stampDataUrl)
                                                <img src="{{ $stampDataUrl }}" class="stamp-img" alt="Cachet">
                                            @endif
                                        </div>
                                    </td>
                                    <td class="ar">{{ \App\Services\PdfService::ar('توقيع الوكيل :') }}</td>
                                </tr>
                                <tr>
                                    <td>Livrée par :<div class="sig-row-line"></div></td>
                                    <td class="ar">{{ \App\Services\PdfService::ar('سلمت من طرف :') }}</td>
                                </tr>
                            </table>

                        </td>

                        {{-- ═══ RIGHT: VEHICULE ═══ --}}
                        <td class="split-right">

                            {{-- État folded into the Matricule/Carburant row (not its own <tr>) —
                                 page 1 sits in a fixed-height frame (see .page-frame above); an
                                 extra bordered row here was tipping some contracts onto a
                                 spurious 3rd page (page 2 nearly blank, conditions pushed to
                                 page 3). An inline line inside an existing cell costs ~1 text
                                 line instead of a whole padded+bordered row. --}}
                            @php
                                $conditionFr = ['bon_etat' => 'Bon état', 'leger_dommage' => 'Léger dommage', 'accidente' => 'Accidenté', 'hors_service' => 'Hors service'];
                            @endphp
                            <table class="veh-two-table">
                                <tr>
                                    <td>Marque : <b>{{ $vehicle?->brand ?? '—' }}</b><br><span class="ar-sub ar">{{ \App\Services\PdfService::ar('علامة') }}</span></td>
                                    <td>Type : <b>{{ $vehicle?->model ?? '—' }}</b><br><span class="ar-sub ar">{{ \App\Services\PdfService::ar('صنف') }}</span></td>
                                </tr>
                                <tr>
                                    <td>Matricule : <b>{{ $vehicle?->registration_number ?? '—' }}</b><br><span class="ar-sub ar">{{ \App\Services\PdfService::ar('رقم اللوحة') }}</span></td>
                                    <td>
                                        Carburant : <b>{{ ucfirst(str_replace('_', ' ', $vehicle?->fuel_type ?? '—')) }}</b>
                                        — État : <b>{{ $conditionFr[$vehicle?->condition] ?? '—' }}</b>
                                        <br><span class="ar-sub ar">{{ \App\Services\PdfService::ar('وقود') }}</span>
                                    </td>
                                </tr>
                            </table>

                            @php
                                $fuelFr = ['empty' => 'Vide', 'quarter' => '1/4', 'half' => '1/2', 'three_quarters' => '3/4', 'full' => 'Plein'];
                            @endphp
                            <table class="dr-table">
                                <tr>
                                    <th style="width:20%;"></th>
                                    <th>Départ <span class="dr-ar ar">{{ \App\Services\PdfService::ar('الانطلاق') }}</span></th>
                                    <th>Retour prévu <span class="dr-ar ar">{{ \App\Services\PdfService::ar('العودة المتوقعة') }}</span></th>
                                </tr>
                                <tr>
                                    <td class="lbl">Date <span class="dr-ar ar">{{ \App\Services\PdfService::ar('التاريخ') }}</span></td>
                                    <td>{{ $pickup?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ ($returnActual ?? $returnPlanned)?->format('d/m/Y') ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">Km <span class="dr-ar ar">{{ \App\Services\PdfService::ar('كيلومتر') }}</span></td>
                                    <td>{{ $reservation->initial_mileage !== null ? number_format($reservation->initial_mileage) : '—' }}</td>
                                    <td>{{ $reservation->final_mileage !== null ? number_format($reservation->final_mileage) : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">Carburant <span class="dr-ar ar">{{ \App\Services\PdfService::ar('وقود') }}</span></td>
                                    <td>{{ $fuelFr[$reservation->fuel_level_pickup] ?? '—' }}</td>
                                    <td>{{ $fuelFr[$reservation->fuel_level_return] ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">Lieu <span class="dr-ar ar">{{ \App\Services\PdfService::ar('المكان') }}</span></td>
                                    <td>{{ $reservation->pickup_location ?? '—' }}</td>
                                    <td>{{ $reservation->actual_return_location ?? $reservation->return_location ?? $reservation->pickup_location ?? '—' }}</td>
                                </tr>
                            </table>

                            <div class="subhead">Facturation — Location Longue Durée <span class="subhead-ar ar">{{ \App\Services\PdfService::ar('فاتورة الكراء الطويل الأمد') }}</span></div>
                            <table class="fact-table">
                                <tr>
                                    <td class="fl">Durée du contrat :</td>
                                    <td class="fv">{{ $reservation->total_months ?? '—' }} mois</td>
                                    <td class="fa ar">{{ \App\Services\PdfService::ar('مدة العقد :') }}</td>
                                </tr>
                                <tr>
                                    <td class="fl">Loyer mensuel :</td>
                                    <td class="fv">{{ number_format($reservation->monthly_rate ?? 0, 2) }} / mois</td>
                                    <td class="fa ar">{{ \App\Services\PdfService::ar('الكراء الشهري :') }}</td>
                                </tr>
                                <tr>
                                    <td class="fl">Divers :</td>
                                    <td class="fv">{{ ($reservation->additional_fees ?? 0) > 0 ? number_format($reservation->additional_fees, 2) : 'x' }}</td>
                                    <td class="fa ar">{{ \App\Services\PdfService::ar('مختلفة :') }}</td>
                                </tr>
                                <tr>
                                    <td class="fl">Total H.T. :</td>
                                    <td class="fv"><b>{{ number_format($reservation->subtotal ?? 0, 2) }}</b></td>
                                    <td class="fa ar">{{ \App\Services\PdfService::ar('المجموع بدون الضريبة :') }}</td>
                                </tr>
                                <tr>
                                    <td class="fl">T.V.A % :</td>
                                    <td class="fv">{{ $company['tva_rate'] ?? '—' }}</td>
                                    <td class="fa ar">{{ \App\Services\PdfService::ar('الضريبة :') }}</td>
                                </tr>
                                <tr>
                                    <td class="fl">Total contrat T.T.C. :</td>
                                    <td class="fv"><b>{{ number_format($reservation->total_amount ?? 0, 2) }}</b></td>
                                    <td class="fa ar">{{ \App\Services\PdfService::ar('مجموع العقد مع الضريبة :') }}</td>
                                </tr>
                            </table>

                            <div class="subhead">État du Véhicule <span class="subhead-ar ar">{{ \App\Services\PdfService::ar('حالة السيارة') }}</span></div>
                            <div class="etat-wrap">
                                <span class="chk"></span> Griffe &nbsp;
                                <span class="chk"></span> Rayure &nbsp;
                                <span class="chk"></span> Accident &nbsp;
                                <span class="chk"></span> Choc
                                <table class="car-diagram-table">
                                    <tr>
                                        <td style="width:70%;"><div class="car-box"></div></td>
                                        <td style="width:30%;">
                                            <div class="fuel-gauge">E&nbsp;&nbsp;&nbsp;F</div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <table class="sig-row-table">
                                <tr>
                                    <td style="width:60%;">Signature du Client :<div class="sig-row-line"></div></td>
                                    <td class="ar" style="width:40%;">{{ \App\Services\PdfService::ar('توقيع الزبون :') }}</td>
                                </tr>
                            </table>

                            <div class="subhead">Mode de Règlement <span class="subhead-ar ar">{{ \App\Services\PdfService::ar('طريقة الأداء') }}</span></div>
                            <table class="modereg-table">
                                <tr>
                                    <td style="width:50%;">
                                        <span class="chk {{ $payMethod === 'check' ? 'on' : '' }}"></span> Chèque
                                    </td>
                                    <td class="ar" style="width:50%;">{{ \App\Services\PdfService::ar('نقدا') }} <span class="chk {{ $payMethod === 'cash' ? 'on' : '' }}"></span> Espèce</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Référence : — <span class="ar" style="float:right;">{{ \App\Services\PdfService::ar('المرجع :') }}</span></td>
                                </tr>
                                <tr>
                                    <td colspan="2">Date : {{ now()->format('d/m/Y') }} <span class="ar" style="float:right;">{{ \App\Services\PdfService::ar('التاريخ :') }}</span></td>
                                </tr>
                                <tr>
                                    <td colspan="2">Loyer payable d'avance, le 1ᵉʳ mois dû dès la signature <span class="ar" style="float:right;">{{ \App\Services\PdfService::ar('الكراء يؤدى مسبقا، الشهر الأول عند التوقيع') }}</span></td>
                                </tr>
                            </table>

                            <table class="sig-row-table">
                                <tr>
                                    <td style="width:60%;">Signature du Client :<div class="sig-row-line"></div></td>
                                    <td class="ar" style="width:40%;">{{ \App\Services\PdfService::ar('توقيع الزبون :') }}</td>
                                </tr>
                            </table>

                        </td>
                    </tr>
                </table>

            </td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td>
                <div class="footer-note">Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $reservation->agency->name ?? $company['name'] ?? config('app.name') }}</div>
            </td></tr>
    </table>
</div>{{-- /.page-pad (page 1) --}}

{{-- ══════════════════════════ PAGE 2 — CONDITIONS GENERALES ══════════════════════════ --}}
<div class="page-break"></div>
<div class="page-pad">
    <table class="page-frame">
        <tr><td class="cgl-frame">

                <div class="cgl-title"><h1>CONDITIONS GENERALES DE LOCATION LONGUE DUREE</h1></div>
                <div class="cgl-intro">
                    Le present contrat a été etabli et prend date comme indiqué au verso. il engage qui sera appelé le loueur et la personne Société
                    ou Compagnie par qui est signé ce contrat, qui sera dénommée " le locataire"
                </div>

                <table class="cgl-columns">
                    <tr>
                        <td>
                            <div class="cgl-article">
                                <h4>Art. 1- UTILISATION DE LA VOITURE-</h4>
                                <p>Le locataire s'engage à ne pas laisser conduire la voiture par d'autres personnes que lui même ou celles agréées
                                    par le loueur et dont il se porte garant, et à réutiliser le véhicule que pour ses besoins personnels. il est interdit
                                    de participer à toute compétition, quelle que soit, et d'utiliser le véhicule des fins illicites ou des transports de
                                    marchandises. Le locataire s'engage à ne pas solliciter directement des documents douaniers- Il est interdit au
                                    locataire de surcharger le véhicule loué en transportant un nombre de passagers supérieur à celui porté sur le
                                    contrat, sous peie d'être déchu de l'Assurances.</p>
                            </div>
                            <div class="cgl-article">
                                <h4>Art. 2- ETAT DE LA VOITURE-</h4>
                                <p>La voiture est livrée en parfait état de marche et de propreté. Les compteurs et leurs prises sont plombés, et les
                                    plombs ne pourront être enlevés ou volés sous peine de devoir payer la location sur la base de 500 Kilomètres par
                                    jour. La voiture sera rendue dans le même état de propreté; a défaut le locataire devra acquitter le montant de ces
                                    nettoyage et remises en état les 5 pneus sont en bon état sans coupures, l'usure et normale. en cas de détérioration
                                    et de l'un d'eux pour une cause autre que l'usure normale. Le locataire s'engage à le remplacer immédiatement par un
                                    pneu neuf de mêmes dimensions ou d'en payer le montant.</p>
                            </div>
                            <div class="cgl-article">
                                <h4>Art. 3- ESSENCE ET HUILE-</h4>
                                <p>L'essence est à la charge du client. Le locataire doit vérifier en permanence les niveaux d'huile et d'eau, et
                                    vérifier les niveaux de la boîte de vitesse et du pont arrière tous les 1.000 Kilomètre. Il justifiera de ces
                                    travaux par les factures correspondantes (qui lui seront remboursées) sous peine d'avoir à payer une indemnité
                                    pour usure anormale.</p>
                            </div>
                            <div class="cgl-article">
                                <h4>Art. 4- ENTRETIEN ET REPARATION-</h4>
                                <p>L'usure mécanique normale est à la charge du loueur. Toutes les réparations provenant, soit d'une usure
                                    anormale, soit d'une négligence de la part du locataire ou d'une cause accindentelle, seront à la charge et
                                    exécutées par ses soins. Dans le cas ou le véhicule serait immobilisé en dehors de la région, les réparations
                                    qu'elles soient dues a l'usure normale ou à une cause accidentelle, ne seront exécutées qu'après accord
                                    télégraphique du loueur ou par l'Agent régional de la marque de véhicule. Elles devront faire l'objet d'une
                                    facture acquittée et très détaillée. Les pièces défectueuses remplacées devront être présentées avec la facture
                                    acquittée, En aucun cas et n'a aucune circonstance, le locataire ne pourra réclamer des dommages et intérêts,
                                    soit pour le retard de la remise de la voiture, ou annulation de la location, soit pour immobilisation dans le
                                    cas de réparations nécessitées par l'usure normale et effectuées au cours de la location. la responsabilité du
                                    loueur ne pourra jamais être invoquée, même en cas d'accidents de personnes ou de choses ayant pu résulter de
                                    vices ou de défauts de construction ou de réparations antérieures.</p>
                            </div>
                            <div class="cgl-article">
                                <h4>Art. 5- ASSURANCE-</h4>
                                <p>Le locataire est garanti pour les risques suivants : En cas d'accidents fortuit ou fotif le Locataire est
                                    entièrement responsable des dommages causés au véhicule en conséquence. il est tenu de nous régler le montant
                                    total des réparations Le locataire est le seul conducteur du véhicule et s'engage à ne pas céder à autrui à
                                    moins d'une stipulation sur le présent contrat, Les frais de rapatriement et d'immobilisation reste toujours à
                                    la charge du locataire, quelle que soit la formule d'assurance contractes. ASSU-RANCE-Assure tières illimitée
                                    vol et incendie est inclus dans le prix de location assure complémentaire de 35 Dh par jour en cas d'accident
                                    fau-tif 30% et fortuite 50% reste à la charge du client. Assure des personnes transportés peut être souscrite
                                    pour 15 Dh par jour. La voiture n'est assurée que pour la durée de location. Passé ce délai, le locataire décline
                                    toute résponsabilité pour les accidents que le locataire aurait pu causer et dont il devra faire son affaire
                                    personnelle. Enfin; il n'y a pas d'Assurance pour tout conducteur non muni d'un per-mis en état de validité ou
                                    d'une permis datant de moins de l'an. le loueur décline toutes responsabilité pour les accidents aux tiers ou
                                    dégat à la voiture que Je locataire pourrait causer pendant la période de laocation si le locataire a
                                    délibérement fourni au loueur des informations fausses concernant son identité, son adresse ou la validité de
                                    son permis de conduire.</p>
                            </div>
                        </td>
                        <td>
                            <div class="cgl-article">
                                <h4>Art. 6- LOYER, CAUTION, RECONDUCTION-</h4>
                                <p>Le loyer mensuel, ainsi que la caution, sont payables d'avance : le premier mois est dû dès la signature du
                                    présent contrat, chaque mois suivant devenant exigible à sa date anniversaire. Tout mois de location commencé
                                    est dû en entier. La caution ne pourra servir, en aucun cas, à régler un loyer échu. A défaut de règlement d'un
                                    mois échu, le loueur pourra mettre fin au contrat après mise en demeure restée sans effet, sans préjudice des
                                    sommes déjà dues. Toute résiliation anticipée à l'initiative du locataire donne lieu à facturation au prorata
                                    des mois entamés jusqu'à la restitution effective du véhicule.</p>
                            </div>
                            <div class="cgl-article">
                                <h4>Art. 7- RAPATRIEMENT DE LA VOITURE-</h4>
                                <p>Le locataire s'interdit formellement d'abandonner le véhicule. En cas d'impossibilité matérielle. celle-ci
                                    sera rapatriée aux frais et par les soins du locataire, la location restant due jusqu'à retour du véhicule.</p>
                            </div>
                            <div class="cgl-article">
                                <h4>Art. 8 - PAPIER DE LA VOITURE-</h4>
                                <p>Le locataire remettra dès la fin de la location à la rentrée de la voiture la carte grise et tous les papiers
                                    nécessaires à sa circulation, faute de quoi, ces pièces étant indispensables à de nouvelles locations, la
                                    location continuera a être facturée aux prix initial jusqu'à leur remise à la société. En cas de perte de ces
                                    papiers le locataire devra acquitter les montant des frais de duplicata. ainsi que l'immobilisation.</p>
                            </div>
                            <div class="cgl-article">
                                <h4>Art. 9- RESPONSABILITE-</h4>
                                <p>Le locataire demeure le seule responsable des amendes, contraventions et procés-verbaux établis contre lui.</p>
                            </div>
                            <div class="cgl-article">
                                <h4>Art. 10- COMPETENCE-</h4>
                                <p>De convention expresse et en cas de contestation quelconque, le tribunal de Tanger sera seul compétant, les
                                    frais de timbres et d'enregistrement restant à la charge du locataire</p>
                            </div>
                        </td>
                    </tr>
                </table>

            </td></tr>
    </table>
</div>{{-- /.page-pad (page 2) --}}

</body>
</html>
