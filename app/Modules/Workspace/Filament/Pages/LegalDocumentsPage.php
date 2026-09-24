<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Filament\Pages;

use App\Modules\Workspace\Actions\PublishLegalVersion;
use App\Modules\Workspace\Domain\Enums\LegalChange;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use App\Modules\Workspace\Services\LegalLibrary;
use App\Support\JalaliDate;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * انتشار نسخه تازه صفحات حقوقی.
 *
 * هیچ دکمه «ویرایش» نیست: نسخه منتشرشده تغییر نمی‌کند (متنی که کاربری پذیرفته
 * باید بماند). متن جاری در فرم پیش‌پر می‌شود تا نسخه تازه از روی آن نوشته شود.
 *
 * انتخاب «تغییر اساسی» همه کاربران واردشده را در صفحه بعدی‌شان به صفحه
 * پذیرش می‌برد؛ برای همین از جنس تصمیم مدیر ارشد است.
 */
final class LegalDocumentsPage extends Page
{
    public const ABILITY = 'admin.legal.manage';

    protected static ?string $slug = 'legal-documents';

    protected static ?int $navigationSort = 80;

    protected string $view = 'workspace::filament.pages.legal-documents';

    public string $document = 'terms';

    public string $change = 'minor';

    public string $summary = '';

    public string $body = '';

    public string $effectiveAt = '';

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'صفحات حقوقی';
    }

    public function getTitle(): string
    {
        return 'صفحات حقوقی';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(LegalLibrary $library): void
    {
        $this->loadCurrent($library);
    }

    public function updatedDocument(LegalLibrary $library): void
    {
        $this->loadCurrent($library);
    }

    /** @return list<array{value: string, label: string, version: int|null, date: string|null, change: string|null}> */
    public function rows(LegalLibrary $library): array
    {
        $rows = [];

        foreach (LegalDocument::cases() as $document) {
            $current = $library->current($document);

            $rows[] = [
                'value' => $document->value,
                'label' => $document->label(),
                'version' => $current?->version,
                'date' => $current === null ? null : JalaliDate::long($current->effective_at),
                'change' => $current?->change->label(),
            ];
        }

        return $rows;
    }

    /** @return list<array{value: string, label: string}> */
    public function documentOptions(): array
    {
        return array_map(static fn (LegalDocument $d): array => ['value' => $d->value, 'label' => $d->label()], LegalDocument::cases());
    }

    /** @return list<array{value: string, label: string}> */
    public function changeOptions(): array
    {
        return array_map(static fn (LegalChange $c): array => ['value' => $c->value, 'label' => $c->label()], LegalChange::cases());
    }

    public function publish(PublishLegalVersion $publish): void
    {
        $this->error = null;

        $document = LegalDocument::tryFrom($this->document);
        $change = LegalChange::tryFrom($this->change);

        if ($document === null || $change === null) {
            $this->error = 'سند یا نوع تغییر نامعتبر است.';

            return;
        }

        $actorId = Auth::id();

        try {
            $version = $publish->handle(
                $document,
                $this->body,
                $change,
                $this->summary,
                $this->effectiveAt !== '' ? Carbon::parse($this->effectiveAt) : null,
                is_int($actorId) ? $actorId : null,
            );
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->reset(['summary', 'effectiveAt']);
        $this->change = LegalChange::Minor->value;

        Notification::make()
            ->title(sprintf('نسخه %d «%s» منتشر شد', $version->version, $document->label()))
            ->success()
            ->send();
    }

    private function loadCurrent(LegalLibrary $library): void
    {
        $document = LegalDocument::tryFrom($this->document);

        $this->body = $document === null ? '' : ($library->current($document)->body ?? '');
    }
}
