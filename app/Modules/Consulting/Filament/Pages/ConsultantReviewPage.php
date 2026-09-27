<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Filament\Pages;

use App\Modules\Consulting\Actions\ReviewConsultantProfile;
use App\Modules\Consulting\Admin\PendingConsultantProfiles;
use App\Modules\Consulting\Domain\ConsultantDocument;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\Enums\ProfileReviewStatus;
use App\Modules\Consulting\Domain\ProfileDraft;
use App\Modules\Consulting\Services\ConsultantPresenter;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use App\Support\Regions\Regions;
use App\Support\Taxonomy\TermData;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * صف تأیید صفحه مشاوران: هر ویرایش کنار نسخه منتشرشده، با مدرک‌های خصوصی.
 *
 * دکمه‌ها همان Action را صدا می‌زنند تا رویداد، اعلان و دفتر رویداد دور
 * زده نشود.
 */
final class ConsultantReviewPage extends Page
{
    protected static ?string $slug = 'consultant-review';

    protected static ?int $navigationSort = 47;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected string $view = 'consulting::filament.pages.consultant-review';

    /** @var list<array{id: int, name: string, meta: string, photo: string|null, fields: list<array{label: string, value: string, changed: bool}>, documents: list<array{name: string, url: string}>, url: string|null}> */
    public array $rows = [];

    /** @var array<string, string> */
    public array $notes = [];

    public static function getNavigationLabel(): string
    {
        return 'صفحه مشاوران';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ConsultantProfile::query()->where('status', ProfileReviewStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'صفحه مشاوران — صف تأیید';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingConsultantProfiles::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function approve(int $id, ReviewConsultantProfile $review): void
    {
        $this->decide(fn () => $review->approve($this->profile($id), (int) Auth::id()), 'صفحه مشاور منتشر شد');
    }

    public function reject(int $id, ReviewConsultantProfile $review): void
    {
        $this->decide(fn () => $review->reject($this->profile($id), (int) Auth::id(), $this->notes['p'.$id] ?? ''), 'برای اصلاح برگشت');
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

    private function profile(int $id): ConsultantProfile
    {
        return ConsultantProfile::query()->findOrFail($id);
    }

    private function load(): void
    {
        $presenter = app(ConsultantPresenter::class);
        $regions = app(Regions::class);
        $terms = collect($presenter->domainTerms())->mapWithKeys(static fn (TermData $term): array => [$term->id => $term->name]);

        $this->notes = [];
        $this->rows = ConsultantProfile::query()
            ->where('status', ProfileReviewStatus::Pending)
            ->with('documents')
            ->oldest('submitted_at')
            ->get()
            ->map(static function (ConsultantProfile $profile) use ($presenter, $regions, $terms): array {
                $draft = ProfileDraft::fromArray($profile->pending ?? []);
                $current = $profile->published_at === null ? null : $profile->publishedDraft(
                    array_map(static fn (TermData $term): int => $term->id, $presenter->domainsOf($profile)),
                );
                $domains = static fn (ProfileDraft $d): string => implode('، ', array_map(static fn (int $id): string => (string) ($terms[$id] ?? '#'.$id), $d->domainIds));
                $place = static fn (ProfileDraft $d): string => implode('، ', array_filter([$regions->cityName($d->city), $regions->provinceName($d->province)]));

                $fields = [
                    ['نام نمایشی', static fn (ProfileDraft $d): string => $d->displayName],
                    ['نشانی صفحه', static fn (ProfileDraft $d): string => '/consultants/'.$d->slug],
                    ['عنوان کوتاه', static fn (ProfileDraft $d): string => $d->headline],
                    ['شهر', $place],
                    ['حوزه‌ها', $domains],
                    ['معرفی', static fn (ProfileDraft $d): string => $d->bio],
                    ['سابقه (به اظهار مشاور)', static fn (ProfileDraft $d): string => (string) $d->experience],
                    ['تحصیلات (به اظهار مشاور)', static fn (ProfileDraft $d): string => (string) $d->education],
                    ['عکس', static fn (ProfileDraft $d): string => $d->photoId === null ? 'ندارد' : 'تصویر #'.$d->photoId],
                ];

                return [
                    'id' => $profile->id,
                    'name' => $draft->displayName,
                    'meta' => implode(' · ', [
                        $current === null ? 'صفحه تازه' : 'ویرایش صفحه منتشرشده',
                        'کاربر #'.$profile->user_id,
                        JalaliDate::long($profile->submitted_at ?? $profile->updated_at),
                    ]),
                    'photo' => $presenter->photo($draft->photoId)?->url,
                    'fields' => array_map(static fn (array $field): array => [
                        'label' => $field[0],
                        'value' => $field[1]($draft),
                        'changed' => $current !== null && $field[1]($current) !== $field[1]($draft),
                    ], $fields),
                    'documents' => $profile->documents->map(static fn (ConsultantDocument $document): array => [
                        'name' => $document->original_name,
                        'url' => route('consulting.documents.download', $document->uuid),
                    ])->values()->all(),
                    'url' => $profile->published_at === null ? null : route('consulting.show', $profile->slug),
                ];
            })
            ->values()
            ->all();
    }
}
