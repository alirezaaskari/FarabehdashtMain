<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Filament\Pages;

use App\Modules\Webinars\Actions\ChangeWebinarStatus;
use App\Modules\Webinars\Actions\SaveWebinar;
use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Modules\Webinars\Domain\Webinar;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/** رویداد و وبینار: ساخت، انتشار، لغو (با بازگشت وجه به کیف پول) و پیوند ضبط. */
final class WebinarsPage extends Page
{
    public const ABILITY = 'admin.content.publish';

    protected static ?string $slug = 'webinars';

    protected static ?int $navigationSort = 49;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected string $view = 'webinars::filament.pages.webinars';

    /** @var array{title: string, slug: string, description: string, instructor_name: string, starts_at: string, duration_minutes: string, capacity: string, price: string, join_url: string, recording_url: string} */
    public array $form = self::BLANK;

    public ?int $editingId = null;

    /** @var list<array{id: int, title: string, status: string, when: string, price: string, registered: int, capacity: int, url: string|null}> */
    public array $webinars = [];

    private const array BLANK = [
        'title' => '',
        'slug' => '',
        'description' => '',
        'instructor_name' => '',
        'starts_at' => '',
        'duration_minutes' => '60',
        'capacity' => '100',
        'price' => '0',
        'join_url' => '',
        'recording_url' => '',
    ];

    public static function getNavigationLabel(): string
    {
        return 'رویداد و وبینار';
    }

    public function getTitle(): string
    {
        return 'رویداد و وبینار';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function edit(int $id): void
    {
        $webinar = Webinar::query()->findOrFail($id);

        $this->editingId = $webinar->id;
        $this->form = [
            'title' => $webinar->title,
            'slug' => $webinar->slug,
            'description' => $webinar->description,
            'instructor_name' => $webinar->instructor_name,
            'starts_at' => $webinar->starts_at->format('Y-m-d\TH:i'),
            'duration_minutes' => (string) $webinar->duration_minutes,
            'capacity' => (string) $webinar->capacity,
            'price' => (string) $webinar->price_toman,
            'join_url' => $webinar->join_url,
            'recording_url' => (string) $webinar->recording_url,
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->form = self::BLANK;
    }

    public function save(SaveWebinar $save): void
    {
        $webinar = $this->editingId !== null ? Webinar::query()->findOrFail($this->editingId) : null;

        try {
            $save->handle($this->form, $this->actorId(), $webinar);
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('ذخیره نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($webinar === null ? 'رویداد ساخته شد (پیش‌نویس)' : 'رویداد ذخیره شد')->success()->send();
        $this->cancelEdit();
        $this->load();
    }

    public function publish(int $id, ChangeWebinarStatus $status): void
    {
        $this->change(fn () => $status->publish(Webinar::query()->findOrFail($id), $this->actorId()), 'رویداد منتشر شد');
    }

    public function cancel(int $id, ChangeWebinarStatus $status): void
    {
        $this->change(fn () => $status->cancel(Webinar::query()->findOrFail($id), $this->actorId()), 'رویداد لغو شد و مبلغ‌ها به کیف پول برگشت');
    }

    private function change(callable $action, string $done): void
    {
        try {
            $action();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($done)->success()->send();
        $this->load();
    }

    private function actorId(): int
    {
        return (int) Auth::id();
    }

    private function load(): void
    {
        $this->webinars = Webinar::query()
            ->withCount(['registrations as registered_count' => static fn ($q) => $q->where('status', RegistrationStatus::Confirmed)])
            ->orderByDesc('starts_at')
            ->get()
            ->map(static fn (Webinar $webinar): array => [
                'id' => $webinar->id,
                'title' => $webinar->title,
                'status' => $webinar->status->label(),
                'when' => JalaliDate::longWithTime($webinar->starts_at),
                'price' => $webinar->isFree() ? 'رایگان' : $webinar->price()->format(),
                'registered' => (int) $webinar->getAttribute('registered_count'),
                'capacity' => $webinar->capacity,
                'url' => $webinar->isPublished() ? route('webinars.show', $webinar->slug) : null,
            ])
            ->values()
            ->all();
    }
}
