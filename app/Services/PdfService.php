<?php

namespace App\Services;

use App\Models\BillingDocument;
use App\Models\Reservation;
use App\Models\Setting;
use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use NumberToWords\NumberToWords;

class PdfService
{
    private static ?Arabic $arabicShaper = null;

    /**
     * dompdf has no real bidi/text-shaping engine — it draws glyphs in
     * whatever order the string is given, left to right (see
     * https://github.com/dompdf/dompdf/issues/712 and friends). Relying on
     * CSS `direction: rtl` to reorder Arabic at render time is unreliable,
     * especially once French and Arabic text share a line, and produces
     * mirrored/scrambled words. The fix used throughout the ecosystem
     * (ar-php, the basis of several laravel-dompdf-arabic wrapper packages)
     * is to pre-shape and reorder Arabic into its final *visual* glyph
     * sequence in PHP, then render it as plain left-to-right text — dompdf
     * then just has to draw characters in the order given, which it does
     * reliably.
     */
    public static function ar(string $text): string
    {
        self::$arabicShaper ??= new Arabic();

        // utf8Glyphs() has its own internal plain-text word-wrap (default
        // max_chars=50, meant for console output): past that length it
        // splits the string into chunks at word boundaries and reverses
        // each chunk's word order independently, joining them with "\n".
        // Since the result is dropped into inline HTML — where actual
        // line-wrapping is handled by CSS/dompdf and "\n" just collapses to
        // whitespace — that internal split silently scrambles word order
        // for any sentence longer than 50 chars (two correctly-shaped
        // fragments end up concatenated in the wrong order). Passing a
        // max_chars past the string's own length disables that internal
        // wrap so the whole string is reordered as one RTL run.
        return self::$arabicShaper->utf8Glyphs($text, mb_strlen($text) + 1);
    }
    /**
     * Shared dompdf options for the contract PDF. Font subsetting keeps
     * output size sane (unsubsetted DejaVu Sans alone is ~1MB).
     *
     * isRemoteEnabled must be TRUE — the contract's @font-face rule (Arabic
     * labels, see prepareContractData()) loads a local TTF via a file:// url(),
     * and dompdf's CSS resolver only processes @font-face sources at all when
     * remote fetching is on, even for local files. mergeWithDefaults=true is
     * passed at the call site so this doesn't blow away the chroot Laravel
     * configures (a bare `new Options($options)` with unlisted keys resets
     * chroot, which silently breaks local file:// resolution).
     */
    private const CONTRACT_PDF_OPTIONS = [
        'defaultFont'             => 'DejaVu Sans',
        'isHtml5ParserEnabled'    => true,
        'isRemoteEnabled'         => true,
        'isFontSubsettingEnabled' => true,
        'dpi'                     => 96,
        'enable_html5_parser'     => true,
    ];

    public function generateReservationContract(Reservation $reservation, bool $download = false): Response
    {
        $data = $this->prepareContractData($reservation);

        $pdf = Pdf::loadView('pdf.contract', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions(self::CONTRACT_PDF_OPTIONS, true);

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
            ], true);

        $filename = strtolower($document->document_number) . '.pdf';

        return $download
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function saveReservationContractToMedia(Reservation $reservation): string
    {
        $data = $this->prepareContractData($reservation);

        $pdf = Pdf::loadView('pdf.contract', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions(self::CONTRACT_PDF_OPTIONS, true);

        $filename = 'contract-' . $reservation->reservation_number . '.pdf';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($tmpPath, $pdf->output());

        // Never overwrite an already-generated contract — once validated, the
        // stored file is the source of truth even if the reservation changes later.
        $reservation->addMedia($tmpPath)
            ->usingFileName($filename)
            ->toMediaCollection('contract');

        return $reservation->getFirstMediaUrl('contract');
    }

    private function prepareContractData(Reservation $reservation): array
    {
        $reservation->loadMissing(['agency', 'vehicle', 'client', 'creator', 'validator']);

        $logoDataUrl = null;
        if ($reservation->agency) {
            $media = $reservation->agency->getFirstMedia('logo');
            if ($media && file_exists($media->getPath())) {
                $logoDataUrl = 'data:' . $media->mime_type . ';base64,'
                    . base64_encode(file_get_contents($media->getPath()));
            }
        }

        $signatureDataUrl = null;
        $stampDataUrl = null;
        if ($reservation->validator && $reservation->agency_id) {
            $membership = $reservation->validator->agencies()
                ->where('agencies.id', $reservation->agency_id)
                ->first();

            if ($membership?->pivot->signature_path) {
                $signatureDataUrl = $this->diskFileToDataUrl($membership->pivot->signature_path);
            }
            if ($membership?->pivot->stamp_path) {
                $stampDataUrl = $this->diskFileToDataUrl($membership->pivot->stamp_path);
            }
        }

        // dompdf writes its processed font cache here on first use of a
        // custom @font-face — must exist and be writable or the font is
        // silently dropped (falls back to defaultFont, which has no Arabic
        // glyphs, rendering as "?").
        File::ensureDirectoryExists(storage_path('fonts'));

        return [
            'reservation'      => $reservation,
            'company'          => Setting::where('group', 'company')->pluck('value', 'key')->toArray(),
            'logoDataUrl'      => $logoDataUrl,
            'signatureDataUrl' => $signatureDataUrl,
            'stampDataUrl'     => $stampDataUrl,
            'arabicFontRegular' => $this->localFontUrl('Amiri-Regular.ttf'),
            'arabicFontBold'    => $this->localFontUrl('Amiri-Bold.ttf'),
        ];
    }

    private function diskFileToDataUrl(string $path): ?string
    {
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }
        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/png';
        return 'data:' . $mimeType . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    }

    /**
     * Build a dompdf-resolvable file:// url() for a bundled font. On Windows,
     * dompdf's url resolver only succeeds with exactly two slashes before the
     * drive letter (file://C:/...) — a third slash (file:///C:/...) makes
     * realpath() fail and the @font-face rule is dropped without error.
     */
    private function localFontUrl(string $filename): string
    {
        return 'file://' . str_replace('\\', '/', resource_path('fonts/' . $filename));
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
            ], true);

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
