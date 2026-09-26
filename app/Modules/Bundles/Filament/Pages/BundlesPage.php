<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Filament\Pages;

use App\Modules\Bundles\Actions\ChangeBundleStatus;
use App\Modules\Bundles\Actions\SaveBundle;
use App\Modules\Bundles\Domain\Bundle;
use App\Modules\Bundles\Services\ComponentCatalog;
use App\Support\Admin\NavigationGroup;
use App\Support\Bundles\BundleComponent;
use App\Support\Money;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * بسته‌های راه‌حل: فقط مدیر می‌سازد و قیمت بسته باید کمتر از جمع اجزا باشد (DEC-47).
 */
final class BundlesPage extends Page
{
    public const ABILITY = 'admin.content.publish';

    protected static ?string $slug = 'bundles';

    protected static ?int $navigationSort = 48;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected string $view = 'bundles::filament.pages.bundles';

    /** @var array{title: string, slug: string, description: string, price: string, items: list<string>} */
    public array $form = self::BLANK;

    public ?int $editingId = null;

    /** @var list<array{id: int, title: string, status: string, price: string, listTotal: string, items: int, url: string|null}> */
    public array $bundles = [];

    /** @var list<array{label: string, options: array<string, string>}> */
    public array $groups = [];

    private const array BLANK = [
        'title' => '',
        'slug' => '',
        'description' => '',
        'price' => '',
        'items' => [],
    ];

    public static function getNavigationLabel(): string
    {
        return 'بسته‌های راه‌حل';
    }

    public function getTitle(): string
    {
        return 'بسته‌های راه‌حل';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(ComponentCatalog $catalog): void
    {
        $this->load($catalog);
    }

    public function edit(int $id): void
    {
        $bundle = Bundle::query()->with('items')->findOrFail($id);

        $this->editingId = $bundle->id;
        $this->form = [
            'title' => $bundle->title,
            'slug' => $bundle->slug,
            'description' => $bundle->description,
            'price' => (string) $bundle->price_toman,
            'items' => $bundle->items->map->key()->values()->all(),
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->form = self::BLANK;
    }

    public function save(SaveBundle $save, ComponentCatalog $catalog): void
    {
        $bundle = $this->editingId !== null ? Bundle::query()->findOrFail($this->editingId) : null;

        try {
            $save->handle($this->form, $this->actorId(), $bundle);
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('ذخیره نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($bundle === null ? 'بسته ساخته شد (پیش‌نویس)' : 'بسته ذخیره شد')->success()->send();
        $this->cancelEdit();
        $this->load($catalog);
    }

    public function publish(int $id, ChangeBundleStatus $status, ComponentCatalog $catalog): void
    {
        $this->change(fn () => $status->publish(Bundle::query()->findOrFail($id), $this->actorId()), 'بسته منتشر شد', $catalog);
    }

    public function retire(int $id, ChangeBundleStatus $status, ComponentCatalog $catalog): void
    {
        $this->change(fn () => $status->retire(Bundle::query()->findOrFail($id), $this->actorId()), 'بسته از فروش برداشته شد', $catalog);
    }

    private function change(callable $action, string $done, ComponentCatalog $catalog): void
    {
        try {
            $action();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($done)->success()->send();
        $this->load($catalog);
    }

    private function actorId(): int
    {
        return (int) Auth::id();
    }

    private function load(ComponentCatalog $catalog): void
    {
        $this->groups = [];

        foreach ($catalog->sources() as $source) {
            $options = [];

            foreach ($source->options() as $component) {
                $options[$component->key()] = $component->title.' — '.$component->listPrice->format();
            }

            $this->groups[] = ['label' => $source->label(), 'options' => $options];
        }

        $this->bundles = Bundle::query()
            ->with('items')
            ->orderBy('title')
            ->get()
            ->map(static function (Bundle $bundle) use ($catalog): array {
                $total = Money::zero();

                foreach ($catalog->resolve($bundle) as $row) {
                    if ($row['component'] instanceof BundleComponent) {
                        $total = $total->plus($row['component']->listPrice);
                    }
                }

                return [
                    'id' => $bundle->id,
                    'title' => $bundle->title,
                    'status' => $bundle->status->label(),
                    'price' => $bundle->price()->format(),
                    'listTotal' => $total->format(),
                    'items' => $bundle->items->count(),
                    'url' => $bundle->isPublished() ? route('bundles.show', $bundle->slug) : null,
                ];
            })
            ->values()
            ->all();
    }
}
