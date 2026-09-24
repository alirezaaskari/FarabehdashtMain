<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Domain\ReportDocument;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * ساخت PDF گزارش با mPDF (DEC-26).
 *
 * mPDF انتخاب شد چون PHP خالص است و روی هاست اشتراکی cPanel بدون مرورگر یا
 * باینری جانبی کار می‌کند، و حروف فارسی را درست می‌چسباند و راست‌چین
 * می‌کند. فونت Vazirmatn از خود مخزن خوانده می‌شود؛ هیچ درخواست بیرونی
 * در کار نیست.
 */
final readonly class ReportPdf
{
    private const FONT = 'vazirmatn';

    public function __construct(
        private ViewFactory $views,
        private string $fontDirectory,
        private string $stylesheet,
        private string $tempDirectory,
    ) {}

    /**
     * بایت‌های PDF. نشانی تأیید فقط برای گزارش صادرشده داده می‌شود؛
     * پیش‌نمایش QR ندارد و روی هر صفحه‌اش «پیش‌نمایش» نوشته شده است.
     */
    public function render(ReportDocument $document, ?string $verifyUrl = null): string
    {
        $pdf = $this->engine();

        $pdf->SetTitle($document->title);
        $pdf->SetAuthor($document->authorName);
        $pdf->SetCreator('فرابهداشت');

        if ($document->isPreview()) {
            $pdf->SetWatermarkText('پیش‌نمایش', 0.08);
            $pdf->showWatermarkText = true;
        }

        $pdf->WriteHTML((string) file_get_contents($this->stylesheet), HTMLParserMode::HEADER_CSS);
        $pdf->SetHTMLFooter($this->views->make('reports::pdf.footer', ['document' => $document])->render());
        $pdf->WriteHTML(
            $this->views->make('reports::pdf.document', ['document' => $document, 'verifyUrl' => $verifyUrl])->render(),
            HTMLParserMode::HTML_BODY,
        );

        return $pdf->Output('', Destination::STRING_RETURN);
    }

    private function engine(): Mpdf
    {
        $config = (new ConfigVariables)->getDefaults();
        $fonts = (new FontVariables)->getDefaults();

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 16,
            'margin_bottom' => 20,
            'margin_left' => 16,
            'margin_right' => 16,
            'tempDir' => $this->tempDirectory,
            'fontDir' => [...(array) $config['fontDir'], $this->fontDirectory],
            'fontdata' => (array) $fonts['fontdata'] + [
                self::FONT => [
                    'R' => 'Vazirmatn-Regular.ttf',
                    'B' => 'Vazirmatn-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font' => self::FONT,
            'directionality' => 'rtl',
            'autoLangToFont' => false,
            'autoScriptToLang' => false,
            // سرتیتر بخش هرگز تنها در پای صفحه نمی‌ماند و با جدولش می‌رود.
            'use_kwt' => true,
        ]);

        // شماره صفحه با ارقام فارسی، مثل هر عدد دیگر درون جمله فارسی.
        $pdf->PageNumSubstitutions[] = ['from' => 1, 'reset' => 0, 'type' => 'persian', 'suppress' => 'off'];

        return $pdf;
    }
}
