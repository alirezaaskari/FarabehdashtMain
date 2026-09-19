<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Contracts\AuditTrailReader;
use App\Modules\Core\Domain\AuditLog;
use App\Support\Audit\AuditFilter;
use App\Support\Audit\AuditRecord;
use App\Support\Mobile;
use Illuminate\Database\Eloquent\Builder;

/**
 * خواندن دفتر رویداد برای پنل مدیریت.
 *
 * نام بازیگر با یک Join ساده می‌آید، نه با بارگذاری رابطه: این صفحه فقط
 * نمایش است و بارکردن کل مدل کاربر برای هر ردیف، هزینه بی‌دلیل دارد.
 */
final readonly class AuditReader implements AuditTrailReader
{
    /** @return list<AuditRecord> */
    public function search(AuditFilter $filter, int $limit = 50, int $offset = 0): array
    {
        $rows = $this->query($filter)
            ->leftJoin('users', 'users.id', '=', 'audit_logs.actor_id')
            ->orderByDesc('audit_logs.id')
            ->limit($limit)
            ->offset($offset)
            ->get(['audit_logs.*', 'users.name as actor_name', 'users.mobile as actor_mobile']);

        return $rows->map(fn (AuditLog $row): AuditRecord => new AuditRecord(
            id: (int) $row->getKey(),
            action: $row->action,
            createdAt: $row->created_at,
            subjectType: $row->subject_type,
            subjectId: $row->subject_id,
            actorId: $row->actor_id,
            actorName: $this->actorName($row),
            before: $row->before ?? [],
            after: $row->after ?? [],
            context: $row->context ?? [],
            ip: $row->ip,
        ))->all();
    }

    public function count(AuditFilter $filter): int
    {
        return $this->query($filter)->count();
    }

    /** @return list<string> */
    public function knownActions(): array
    {
        /** @var list<string> $actions */
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action')->all();

        return $actions;
    }

    /** @return Builder<AuditLog> */
    private function query(AuditFilter $filter): Builder
    {
        return AuditLog::query()
            ->when($filter->action !== null, fn (Builder $q): Builder => $q->where('audit_logs.action', $filter->action))
            ->when($filter->actorId !== null, fn (Builder $q): Builder => $q->where('audit_logs.actor_id', $filter->actorId))
            ->when($filter->subjectType !== null, fn (Builder $q): Builder => $q->where('audit_logs.subject_type', $filter->subjectType))
            ->when($filter->subjectId !== null, fn (Builder $q): Builder => $q->where('audit_logs.subject_id', $filter->subjectId))
            ->when($filter->from !== null, fn (Builder $q): Builder => $q->where('audit_logs.created_at', '>=', $filter->from))
            ->when($filter->until !== null, fn (Builder $q): Builder => $q->where('audit_logs.created_at', '<=', $filter->until));
    }

    /**
     * نام بازیگر برای نمایش.
     *
     * اگر نامی ثبت نشده باشد، شماره **نیمه‌پوشیده** نشان داده می‌شود نه کامل.
     * قرار بود دفتر رویداد نسخه دومی از داده شخصی نسازد؛ نمایش شماره کامل در
     * صفحه‌ای که هر چهار نقش مدیریتی می‌بینند، همان کار را از راه دیگر می‌کرد.
     */
    private function actorName(AuditLog $row): ?string
    {
        /** @var string|null $name */
        $name = $row->getAttribute('actor_name');

        if (is_string($name) && trim($name) !== '') {
            return $name;
        }

        /** @var string|null $mobile */
        $mobile = $row->getAttribute('actor_mobile');

        if (! is_string($mobile) || $mobile === '') {
            return null;
        }

        return Mobile::tryFromInput($mobile)?->masked() ?? null;
    }
}
