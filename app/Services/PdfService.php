<?php

namespace App\Services;

use App\Models\BillingDocument;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

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
        $document->loadMissing(['agency', 'items', 'reservation', 'client', 'creator']);

        $pdf = Pdf::loadView('pdf.billing-document', compact('document'))
            ->setPaper('A4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', false);

        $filename = strtolower($document->document_number) . '.pdf';

        return $download
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function saveReservationContractToMedia(Reservation $reservation): string
    {
        $reservation->loadMissing(['agency', 'vehicle', 'client', 'creator']);

        $pdf      = Pdf::loadView('pdf.contract', compact('reservation'))
            ->setPaper('A4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

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
        $document->loadMissing(['agency', 'items', 'reservation', 'client', 'creator']);

        $pdf      = Pdf::loadView('pdf.billing-document', compact('document'))
            ->setPaper('A4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = strtolower($document->document_number) . '.pdf';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($tmpPath, $pdf->output());

        $document->clearMediaCollection('document_pdf');
        $document->addMedia($tmpPath)
            ->usingFileName($filename)
            ->toMediaCollection('document_pdf');

        return $document->getFirstMediaUrl('document_pdf');
    }
}