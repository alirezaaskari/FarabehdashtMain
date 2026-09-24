<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\ReportDocument;
use App\Modules\Reports\Domain\TrackingCode;
use App\Modules\Reports\Events\ReportIssued;
use App\Modules\Reports\Services\ReportPdf;
use App\Modules\Reports\Services\ReportSources;
use App\Support\Entitlement\EntitlementDenied;
use App\Support\Entitlement\Feature;
use App\Support\JalaliDate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * صدور گزارش: منجمدکردن داده، ساخت PDF و شناسه رهگیری.
 *
 * سه قاعده این‌جا اجرا می‌شود و هیچ‌جای دیگر:
 *
 * ۱. **داده همین لحظه از منبع خوانده و منجمد می‌شود.** پیش‌نویس فقط به منبع
 *    اشاره می‌کند؛ Snapshot همه چیز را کپی می‌کند تا تغییر بعدی پروژه،
 *    تجهیز یا فرمول به سند صادرشده نرسد.
 * ۲. **هشدار کالیبراسیون جلوی صدور را نمی‌گیرد، تأیید می‌خواهد** (بخش ۸)، و
 *    این تأیید روی خود گزارش چاپ می‌شود.
 * ۳. **PDF یک بار ساخته و هشش ثبت می‌شود** (DEC-27). صفحه تأیید همین هش را
 *    نشان می‌دهد؛ فایلی که هر بار از نو ساخته شود هشی ندارد که بشود به آن
 *    تکیه کرد.
 */
final readonly class IssueReport
{
    public function __construct(
        private ReportSources $sources,
        private ReportPdf $pdf,
        private EntitlementGate $gate,
        private DatabaseManager $db,
        private Dispatcher $events,
        private Filesystem $disk,
        private string $directory,
    ) {}

    public function handle(Report $report, User $user, bool $acknowledgeCalibration = false): Report
    {
        if (! $report->isDraft()) {
            throw new LogicException('این گزارش پیش‌تر صادر شده است.');
        }

        $decision = $this->gate->decide($user, Feature::BuildReport);

        if ($decision->denied()) {
            throw new EntitlementDenied($decision);
        }

        $data = $this->sources->load($report)
            ?? throw new InvalidArgumentException('منبع این گزارش دیگر در دسترس نیست؛ گزارش تازه‌ای بسازید.');

        if ($data->measurements === []) {
            throw new InvalidArgumentException('منبع این گزارش هیچ نتیجه‌ای ندارد.');
        }

        if (trim((string) $report->title) === '' || trim((string) $report->author_name) === '') {
            throw new InvalidArgumentException('عنوان گزارش و نام تهیه‌کننده را در مرحله «مشخصات» وارد کنید.');
        }

        $blocking = $data->blockingEquipment() !== [];

        if ($blocking && ! $acknowledgeCalibration) {
            throw new InvalidArgumentException('دست‌کم یک تجهیز کالیبراسیون معتبر ندارد؛ برای صدور، تأیید آن لازم است.');
        }

        $report->loadMissing('supersedes');
        $code = $this->uniqueCode();
        $document = ReportDocument::fromDraft($report, $data)
            ->issued($code, JalaliDate::long(now()), $blocking);

        $bytes = $this->pdf->render($document, route('reports.verify.show', $code));
        $path = $this->directory.'/'.$report->uuid.'.pdf';

        $this->disk->put($path, $bytes);

        try {
            $this->db->transaction(function () use ($report, $document, $code, $path, $bytes): void {
                $report->forceFill([
                    'status' => ReportStatus::Issued,
                    'title' => $document->title,
                    'tracking_code' => $code,
                    'snapshot' => $document->toArray(),
                    'pdf_path' => $path,
                    'pdf_sha256' => hash('sha256', $bytes),
                    'issued_at' => now(),
                ])->save();

                $previous = $report->supersedes;

                if ($previous !== null && $previous->status === ReportStatus::Issued) {
                    $previous->forceFill([
                        'status' => ReportStatus::Superseded,
                        'superseded_by_id' => $report->id,
                    ])->save();
                }
            });
        } catch (Throwable $exception) {
            $this->disk->delete($path);

            throw $exception;
        }

        $this->events->dispatch(new ReportIssued($report, $blocking, $document->supersedesCode));

        return $report;
    }

    private function uniqueCode(): string
    {
        do {
            $code = TrackingCode::generate()->value;
        } while (Report::query()->where('tracking_code', $code)->exists());

        return $code;
    }
}
