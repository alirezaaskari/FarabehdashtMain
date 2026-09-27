<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * نشان خریدار روی هر صفحه PDF (بخش ۱۸-۱۱).
 *
 * هر صفحه فایل اصلی دست‌نخورده وارد و یک سطر کم‌رنگ پایینش نوشته می‌شود:
 * موبایل پوشیده خریدار و تاریخ دانلود. هدف بازدارندگی از پخش است، نه قفل؛
 * پس هر شکستی (PDF رمزدار، ساختار ناآشنا) بی‌صدا به فایل اصلی برمی‌گردد و
 * خریدار هرگز به‌خاطر نشان از فایلش محروم نمی‌شود.
 */
final readonly class PdfStamp
{
    public function __construct(
        private string $tempDirectory,
        private int $maxBytes,
        private bool $enabled,
        private LoggerInterface $log,
    ) {}

    public function applies(string $path): bool
    {
        return $this->enabled && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }

    /**
     * بایت‌های PDF نشان‌دار، یا null اگر این فایل نشان نمی‌خورد.
     *
     * @param  string  $buyer  شناسه پوشیده خریدار، مثل «0912***1234»
     * @param  string  $date  تاریخ دانلود، با ارقام فارسی
     */
    public function stamp(string $path, string $buyer, string $date): ?string
    {
        $size = @filesize($path);

        if (! $this->applies($path) || $size === false || $size > $this->maxBytes) {
            return null;
        }

        try {
            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'tempDir' => $this->tempDirectory,
                'default_font' => 'dejavusans',
                'directionality' => 'rtl',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
            ]);

            $pages = $pdf->setSourceFile($path);

            for ($page = 1; $page <= $pages; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $width = (float) $size['width'];
                $height = (float) $size['height'];

                $pdf->AddPageByArray([
                    'orientation' => $width > $height ? 'L' : 'P',
                    'sheet-size' => [$width, $height],
                ]);
                $pdf->useTemplate($template);
                $pdf->WriteFixedPosHTML(
                    $this->line($buyer, $date),
                    10, $height - 8, $width - 20, 6, 'visible',
                );
            }

            return $pdf->Output('', Destination::STRING_RETURN);
        } catch (Throwable $exception) {
            $this->log->warning('PDF watermark skipped', ['file' => basename($path), 'error' => $exception->getMessage()]);

            return null;
        }
    }

    /** bdi: موبایل پوشیده («0912***1234») درون سطر راست‌چین وارونه نشود. */
    private function line(string $buyer, string $date): string
    {
        return '<div dir="rtl" style="font-size:7pt;color:gray;text-align:center;">'
            .'نسخه ویژه خریدار <bdi dir="ltr">'.e($buyer).'</bdi> · دانلود '.e($date).' · farabehdasht.com</div>';
    }
}
