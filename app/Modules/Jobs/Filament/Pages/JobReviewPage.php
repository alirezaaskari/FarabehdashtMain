<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Filament\Pages;

use App\Modules\Jobs\Actions\ReviewCompany;
use App\Modules\Jobs\Actions\ReviewPosting;
use App\Modules\Jobs\Admin\PendingJobItems;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\CompanyDocument;
use App\Modules\Jobs\Domain\CompanyDraft;
use App\Modules\Jobs\Domain\Enums\CompanySize;
use App\Modules\Jobs\Domain\Enums\ReviewStatus;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Domain\PostingDraft;
use App\Modules\Jobs\Services\JobCatalog;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use App\Support\Money;
use App\Support\Taxonomy\TermData;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * صف تأیید کاریابی: صفحه شرکت‌ها با مدرک خصوصی (DEC-65) و آگهی‌ها. هر
 * ویرایش کنار نسخه منتشرشده با علامت «تغییر کرده» می‌آید.
 *
 * دکمه‌ها همان Action را صدا می‌زنند تا رویداد، اعلان و دفتر رویداد دور
 * زده نشود.
 */
final class JobReviewPage extends Page
{
    protected static ?string $slug = 'job-review';

    protected static ?int $navigationSort = 48;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected string $view = 'jobs::filament.pages.job-review';

    /** @var list<array{key: string, id: int, kind: string, name: string, meta: string, fields: list<array{label: string, value: string, changed: bool}>, documents: list<array{name: string, url: string}>, url: string|null}> */
    public array $rows = [];

    /** @var array<string, string> */
    public array $notes = [];

    public static function getNavigationLabel(): string
    {
        return 'شرکت‌ها و آگهی‌های شغلی';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Company::query()->where('status', ReviewStatus::Pending)->count()
            + JobPosting::query()->where('status', ReviewStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'کاریابی — صف تأیید';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingJobItems::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function approveCompany(int $id, ReviewCompany $review): void
    {
        $this->decide(fn () => $review->approve(Company::query()->findOrFail($id), (int) Auth::id()), 'صفحه شرکت منتشر شد');
    }

    public function rejectCompany(int $id, ReviewCompany $review): void
    {
        $this->decide(fn () => $review->reject(Company::query()->findOrFail($id), (int) Auth::id(), $this->notes['c'.$id] ?? ''), 'برای اصلاح برگشت');
    }

    public function approvePosting(int $id, ReviewPosting $review): void
    {
        $this->decide(fn () => $review->approve(JobPosting::query()->findOrFail($id), (int) Auth::id()), 'آگهی تأیید شد');
    }

    public function rejectPosting(int $id, ReviewPosting $review): void
    {
        $this->decide(fn () => $review->reject(JobPosting::query()->findOrFail($id), (int) Auth::id(), $this->notes['p'.$id] ?? ''), 'برای اصلاح برگشت');
    }

    private function decide(callable $action, string $done): void
    {
        try {
            $action();
        } catch (RuntimeException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($done)->success()->send();
        $this->load();
    }

    private function load(): void
    {
        $catalog = app(JobCatalog::class);
        $skills = collect($catalog->skills())->mapWithKeys(static fn (TermData $term): array => [$term->id => $term->name]);

        $this->notes = [];
        $this->rows = [
            ...Company::query()->where('status', ReviewStatus::Pending)->with('documents')->oldest('submitted_at')->get()
                ->map(fn (Company $company): array => $this->companyRow($company, $catalog))->values()->all(),
            ...JobPosting::query()->where('status', ReviewStatus::Pending)->with('company')->oldest('submitted_at')->get()
                ->map(fn (JobPosting $posting): array => $this->postingRow($posting, $catalog, $skills->all()))->values()->all(),
        ];
    }

    /** @return array{key: string, id: int, kind: string, name: string, meta: string, fields: list<array{label: string, value: string, changed: bool}>, documents: list<array{name: string, url: string}>, url: string|null} */
    private function companyRow(Company $company, JobCatalog $catalog): array
    {
        $draft = CompanyDraft::fromArray($company->pending ?? []);
        $current = $company->published_at === null ? null : $company->publishedDraft();

        $fields = [
            ['نام شرکت', static fn (CompanyDraft $d): string => $d->name],
            ['نشانی صفحه', static fn (CompanyDraft $d): string => '/companies/'.$d->slug],
            ['صنعت', static fn (CompanyDraft $d): string => $d->industry],
            ['اندازه', static fn (CompanyDraft $d): string => CompanySize::tryFrom($d->size)?->label() ?? ''],
            ['شهر', static fn (CompanyDraft $d): string => $catalog->place($d->province, $d->city)],
            ['معرفی', static fn (CompanyDraft $d): string => $d->about],
            ['نشان', static fn (CompanyDraft $d): string => $d->logoId === null ? 'ندارد' : 'تصویر #'.$d->logoId],
        ];

        return [
            'key' => 'c'.$company->id,
            'id' => $company->id,
            'kind' => 'company',
            'name' => $draft->name,
            'meta' => implode(' · ', [
                $current === null ? 'صفحه شرکت تازه' : 'ویرایش صفحه شرکت',
                'کاربر #'.$company->user_id,
                JalaliDate::long($company->submitted_at ?? $company->updated_at),
            ]),
            'fields' => self::compare($fields, $draft, $current),
            'documents' => $company->documents->map(static fn (CompanyDocument $document): array => [
                'name' => $document->original_name,
                'url' => route('jobs.documents.download', $document->uuid),
            ])->values()->all(),
            'url' => $company->published_at === null ? null : route('jobs.companies.show', $company->slug),
        ];
    }

    /**
     * @param  array<int, string>  $skills
     * @return array{key: string, id: int, kind: string, name: string, meta: string, fields: list<array{label: string, value: string, changed: bool}>, documents: list<array{name: string, url: string}>, url: string|null}
     */
    private function postingRow(JobPosting $posting, JobCatalog $catalog, array $skills): array
    {
        $draft = PostingDraft::fromArray($posting->pending ?? []);
        $current = $posting->approved_at === null ? null : $posting->publishedDraft($catalog->skillIdsOf($posting));
        $salary = static fn (?int $toman): string => $toman === null ? '—' : Money::toman($toman)->format();

        $fields = [
            ['عنوان', static fn (PostingDraft $d): string => $d->title],
            ['شهر', static fn (PostingDraft $d): string => $catalog->place($d->province, $d->city)],
            ['نوع همکاری', static fn (PostingDraft $d): string => $d->employmentType->label()],
            ['سابقه', static fn (PostingDraft $d): string => $catalog->experienceLabel($d->minExperienceYears)],
            ['حقوق', static fn (PostingDraft $d): string => $d->salaryMinToman === null && $d->salaryMaxToman === null ? 'توافقی' : $salary($d->salaryMinToman).' تا '.$salary($d->salaryMaxToman)],
            ['مهارت‌ها', static fn (PostingDraft $d): string => implode('، ', array_map(static fn (int $id): string => $skills[$id] ?? '#'.$id, $d->skillIds))],
            ['شرح', static fn (PostingDraft $d): string => $d->description],
        ];

        return [
            'key' => 'p'.$posting->id,
            'id' => $posting->id,
            'kind' => 'posting',
            'name' => $draft->title,
            'meta' => implode(' · ', [
                $current === null ? 'آگهی تازه' : 'ویرایش آگهی — '.$posting->state()->label(),
                (string) $posting->company->name,
                JalaliDate::long($posting->submitted_at ?? $posting->updated_at),
            ]),
            'fields' => self::compare($fields, $draft, $current),
            'documents' => [],
            'url' => $posting->published_at === null ? null : route('jobs.show', $posting->id),
        ];
    }

    /**
     * @template T of object
     *
     * @param  list<array{0: string, 1: callable(T): string}>  $fields
     * @param  T  $draft
     * @param  T|null  $current
     * @return list<array{label: string, value: string, changed: bool}>
     */
    private static function compare(array $fields, object $draft, ?object $current): array
    {
        return array_map(static fn (array $field): array => [
            'label' => $field[0],
            'value' => $field[1]($draft),
            'changed' => $current !== null && $field[1]($current) !== $field[1]($draft),
        ], $fields);
    }
}
