<?php

declare(strict_types=1);

namespace App\Modules\Reports\Tests;

use App\Modules\Reports\Domain\ReportDocument;
use App\Modules\Reports\Services\ReportPdf;
use App\Support\Reporting\ReportData;
use App\Support\Reporting\ReportEquipment;
use App\Support\Reporting\ReportMeasurement;
use Tests\TestCase;

/**
 * برگه چاپی: چه چیزی روی کاغذ می‌آید و آیا PDF با فونت فارسی ساخته می‌شود.
 */
final class ReportDocumentTest extends TestCase
{
    public function test_the_page_carries_results_method_equipment_acknowledgement_and_disclaimer(): void
    {
        $html = view('reports::pdf.document', [
            'document' => $this->document()->issued('FBH-7K3M-Q9TD', '۲ مهر ۱۴۰۵', true),
            'verifyUrl' => 'https://example.test/verify/FBH-7K3M-Q9TD',
        ])->render();

        $this->assertStringContainsString('85.3 dB', $html);
        $this->assertStringContainsString('noise-dose v2', $html);
        $this->assertStringContainsString('SN-4471', $html);
        $this->assertStringContainsString('تهیه‌کننده با علم به این موضوع', $html);
        $this->assertStringContainsString('تشخیص پزشکی', $html);
        $this->assertStringContainsString('<barcode code="https://example.test/verify/FBH-7K3M-Q9TD"', $html);
    }

    public function test_optional_sections_can_be_left_out(): void
    {
        $document = new ReportDocument(...[...get_object_vars($this->document()), 'includeEquipment' => false, 'includeMethod' => false]);

        $html = view('reports::pdf.document', ['document' => $document, 'verifyUrl' => null])->render();

        $this->assertStringNotContainsString('SN-4471', $html);
        $this->assertStringNotContainsString('روش محاسبه', $html);
        $this->assertStringNotContainsString('<barcode', $html, 'پیش‌نمایش QR ندارد.');
    }

    public function test_the_snapshot_round_trips(): void
    {
        $document = $this->document()->issued('FBH-7K3M-Q9TD', '۲ مهر ۱۴۰۵', false);

        $this->assertEquals($document, ReportDocument::fromArray(json_decode((string) json_encode($document->toArray()), true)));
    }

    public function test_the_pdf_embeds_the_local_persian_font(): void
    {
        $bytes = $this->app->make(ReportPdf::class)->render($this->document());

        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertStringContainsString('Vazirmatn', $bytes);
    }

    private function document(): ReportDocument
    {
        return new ReportDocument(
            title: 'گزارش پایش صدا',
            clientName: 'شرکت نمونه',
            site: 'سالن پرس',
            measuredOn: 'مهر ۱۴۰۵',
            authorName: 'مریم کارشناس',
            findings: "خط اول\nخط دوم",
            recommendations: null,
            includeEquipment: true,
            includeMethod: true,
            data: new ReportData(
                sourceTitle: 'پایش صدا',
                measurements: [
                    new ReportMeasurement('دور اول', 'پرس ۱', 'دوز صدا', '85.3', 'dB', 'noise-dose v2', 7, '۱ مهر ۱۴۰۵'),
                ],
                equipment: [
                    new ReportEquipment(7, 'صداسنج', 'Casella', 'CEL-633', 'SN-4471', 'Class 1', null, '۱ مهر ۱۴۰۴', null, 'منقضی', true),
                ],
            ),
        );
    }
}
