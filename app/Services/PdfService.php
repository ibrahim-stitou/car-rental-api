<?php

namespace App\Services;

use App\Models\BillingDocument;
use App\Models\Reservation;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use NumberToWords\NumberToWords;

class PdfService
{
    public function generateReservationContract(Reservation $reservation, bool $download = false): Response
    {
        $reservation->loadMissing(['agency', 'vehicle', 'client', 'creator']);

        $pdf = Pdf::loadView('pdf.contract', compact('reservation'))
            ->setPaper('A4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', false);

        $filename = 'contract-' . $reservation->reservation_number . '.pdf';

        return $download
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function generateBillingDocument(BillingDocument $document, bool $download = false): Response
    {
        $document->loadMissing(['agency.media', 'items', 'reservation', 'client', 'creator']);

        $data = $this->prepareBillingData($document);

        $pdf = Pdf::loadView('pdf.billing-document', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont'              => 'DejaVu Sans',
                'isHtml5ParserEnabled'     => true,
                'isRemoteEnabled'          => true,
                'isFontSubsettingEnabled'  => true,
                'dpi'                      => 96,
                'enable_css_float'         => false,
                'enable_javascript'        => false,
                'enable_remote'            => true,
                'defaultMediaType'         => 'print',
                'debugKeepTemp'            => false,
                'enable_html5_parser'      => true,
                'isPhpEnabled'             => false,
            ]);

        $filename = strtolower($document->document_number) . '.pdf';

        return $download
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function saveReservationContractToMedia(Reservation $reservation): string
    {
        $reservation->loadMissing(['agency', 'vehicle', 'client', 'creator']);

        // Créer les données pour la vue
        $data = [
            'reservation' => $reservation,
            'company' => Setting::where('group', 'company')->pluck('value', 'key')->toArray(),
        ];

        $pdf = Pdf::loadView('pdf.contract', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont'              => 'DejaVu Sans',
                'isHtml5ParserEnabled'     => true,
                'isRemoteEnabled'          => true,
                'isFontSubsettingEnabled'  => true,
                'dpi'                      => 96,
                'enable_html5_parser'      => true,
            ]);

        $filename = 'contract-' . $reservation->reservation_number . '.pdf';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($tmpPath, $pdf->output());

        $reservation->clearMediaCollection('contract');
        $reservation->addMedia($tmpPath)
            ->usingFileName($filename)
            ->toMediaCollection('contract');

        return $reservation->getFirstMediaUrl('contract');
    }

    public function saveBillingDocumentToMedia(BillingDocument $document): string
    {
        $document->loadMissing(['agency.media', 'items', 'reservation', 'client', 'creator']);

        $data = $this->prepareBillingData($document);

        $pdf = Pdf::loadView('pdf.billing-document', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont'              => 'DejaVu Sans',
                'isHtml5ParserEnabled'     => true,
                'isRemoteEnabled'          => true,
                'isFontSubsettingEnabled'  => true,
                'dpi'                      => 96,
                'enable_html5_parser'      => true,
            ]);

        $filename = strtolower($document->document_number) . '.pdf';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($tmpPath, $pdf->output());

        $document->clearMediaCollection('document_pdf');
        $document->addMedia($tmpPath)
            ->usingFileName($filename)
            ->toMediaCollection('document_pdf');

        return $document->getFirstMediaUrl('document_pdf');
    }

    private function prepareBillingData(BillingDocument $document): array
    {
        $logoDataUrl = null;
        if ($document->agency) {
            $media = $document->agency->getFirstMedia('logo');
            if ($media && file_exists($media->getPath())) {
                $logoDataUrl = 'data:' . $media->mime_type . ';base64,'
                    . base64_encode(file_get_contents($media->getPath()));
            }
        }

        $company = Setting::where('group', 'company')->pluck('value', 'key')->toArray();

        $tvaByRate = [];
        foreach ($document->items as $item) {
            $rate = (float) $item->tax_rate;
            if ($rate > 0) {
                $tvaByRate[$rate] = ($tvaByRate[$rate] ?? 0.0)
                    + (float) $item->total_price * $rate / 100;
            }
        }
        ksort($tvaByRate);

        // Amount in words (French)
        $ntw = new NumberToWords();
        $transformer = $ntw->getNumberTransformer('fr');
        $intAmount   = (int) round((float) $document->total_amount);
        $totalInWords = $transformer->toWords($intAmount);

        $typePhrase = match ($document->type) {
            'FA'  => 'Arrêtée la présente facture',
            'AV'  => "Arrêtée la présente facture d'avoir",
            'DV'  => 'Arrêté le présent devis',
            'BC'  => 'Arrêté le présent bon de commande',
            'BL'  => 'Arrêté le présent bon de livraison',
            'BR'  => 'Arrêté le présent bon de réception',
            default => 'Arrêté le présent document',
        };

        return compact('document', 'logoDataUrl', 'company', 'tvaByRate', 'totalInWords', 'typePhrase');
    }
}
