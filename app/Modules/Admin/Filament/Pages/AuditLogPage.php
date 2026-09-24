<?php

declare(strict_types=1);

namespace App\Modules\Admin\Filament\Pages;

use App\Contracts\AuditTrailReader;
use App\Support\Admin\NavigationGroup;
use App\Support\Audit\AuditFilter;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * نمایشگر دفتر رویداد.
 *
 * فقط خواندنی — نه به این دلیل که صفحه‌اش دکمه ویرایش ندارد، بلکه چون خود مدل
 * `AuditLog` در سطح کد ویرایش و حذف را رد می‌کند.
 *
 * هر چهار نقش مدیریتی `admin.audit.view` را دارند: دفتری که فقط یک نفر
 * ببیندش، کنترل نیست.
 */
final class AuditLogPage extends Page
{
    protected static ?string $slug = 'audit';

    protected static ?int $navigationSort = 90;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected string $view = 'admin::filament.pages.audit-log';

    public const ABILITY = 'admin.audit.view';

    private const PER_PAGE = 40;

    public string $action = '';

    public int $page = 1;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var list<string> */
    public array $actions = [];

    public int $total = 0;

    public static function getNavigationLabel(): string
    {
        return 'دفتر رویداد';
    }

    public function getTitle(): string
    {
        return 'دفتر رویداد';
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->can(self::ABILITY) === true;
    }

    public function mount(AuditTrailReader $reader): void
    {
        $this->actions = $reader->knownActions();
        $this->load($reader);
    }

    public function updatedAction(AuditTrailReader $reader): void
    {
        $this->page = 1;
        $this->load($reader);
    }

    public function goToPage(int $page, AuditTrailReader $reader): void
    {
        $this->page = max(1, $page);
        $this->load($reader);
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / self::PER_PAGE));
    }

    private function load(AuditTrailReader $reader): void
    {
        $filter = new AuditFilter(action: $this->action === '' ? null : $this->action);

        $this->total = $reader->count($filter);

        $this->rows = array_map(static fn ($record): array => [
            'id' => $record->id,
            'action' => $record->action,
            'at' => JalaliDate::longWithTime($record->createdAt),
            'actor' => $record->actorName ?? 'سیستم',
            'subject' => $record->subjectType === null
                ? '—'
                : class_basename($record->subjectType).' #'.$record->subjectId,
            'before' => $record->before,
            'after' => $record->after,
            'context' => $record->context,
            'ip' => $record->ip,
        ], $reader->search($filter, self::PER_PAGE, ($this->page - 1) * self::PER_PAGE));
    }
}
