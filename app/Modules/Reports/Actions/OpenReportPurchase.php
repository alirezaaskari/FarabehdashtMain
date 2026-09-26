<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Modules\Reports\Domain\Enums\ReportPurchaseStatus;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\ReportPurchase;
use App\Modules\Reports\Services\ReportSale;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * خرید «در انتظار پرداخت» برای صدور یک پیش‌نویس، با قیمت امروز.
 *
 * پرداخت رهاشده یا ناموفق ردیفی می‌گذارد که ستون یکتای report_id آن را قفل
 * می‌کند؛ همان ردیف با قیمت امروز دوباره «در انتظار» می‌شود و Authority قبلی
 * پاک می‌شود تا بازگشت دیرِ تلاش قبلی به آن نرسد (همان الگوی ثبت‌نام دوره).
 */
final readonly class OpenReportPurchase
{
    public function __construct(private ReportSale $sale) {}

    public function handle(Report $report): ReportPurchase
    {
        if (! $report->isDraft()) {
            throw new InvalidArgumentException('فقط پیش‌نویس گزارش خریدنی است؛ این گزارش پیش‌تر صادر شده.');
        }

        if (! $this->sale->isOpen()) {
            throw new InvalidArgumentException('خرید تکی گزارش در حال حاضر فعال نیست.');
        }

        if ($this->sale->covers($report)) {
            throw new InvalidArgumentException('صدور این گزارش پیش‌تر پرداخت شده است؛ همین حالا صادرش کنید.');
        }

        $snapshot = [
            'user_id' => $report->user_id,
            'status' => ReportPurchaseStatus::Pending,
            'price_toman' => $this->sale->price()->toman,
            'gateway_authority' => null,
        ];

        $existing = ReportPurchase::query()->where('report_id', $report->id)->first();

        if ($existing !== null) {
            $existing->forceFill($snapshot)->save();

            return $existing;
        }

        return ReportPurchase::query()->create([
            'uuid' => (string) Str::uuid7(),
            'report_id' => $report->id,
            ...$snapshot,
        ]);
    }
}
