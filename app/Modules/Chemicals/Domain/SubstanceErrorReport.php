<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain;

use App\Modules\Chemicals\Domain\Enums\ErrorReportStatus;
use App\Modules\Chemicals\Domain\Enums\ErrorReportTopic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * گزارش یک کاربر که عدد یا متنی در صفحه ماده اشتباه است.
 *
 * گزارش خودش چیزی را عوض نمی‌کند: مدیر منبع را می‌خواند، ماده را از ویرایشگر
 * اصلاح می‌کند (که نسخه و تاریخچه می‌سازد) و بعد گزارش را می‌بندد.
 *
 * @property int $id
 * @property int $substance_id
 * @property int|null $user_id
 * @property ErrorReportTopic $topic
 * @property string $message
 * @property string|null $source_url
 * @property ErrorReportStatus $status
 * @property string|null $admin_note
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Substance $substance
 */
final class SubstanceErrorReport extends Model
{
    protected $table = 'substance_error_reports';

    protected $fillable = ['substance_id', 'user_id', 'topic', 'message', 'source_url'];

    protected $attributes = ['status' => 'open'];

    /** @return BelongsTo<Substance, $this> */
    public function substance(): BelongsTo
    {
        return $this->belongsTo(Substance::class);
    }

    protected function casts(): array
    {
        return [
            'topic' => ErrorReportTopic::class,
            'status' => ErrorReportStatus::class,
            'resolved_at' => 'datetime',
        ];
    }
}
