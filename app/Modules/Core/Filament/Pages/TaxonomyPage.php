<?php

declare(strict_types=1);

namespace App\Modules\Core\Filament\Pages;

use App\Modules\Core\Domain\TaxonomyTerm;
use App\Modules\Core\Events\TaxonomyTermChanged;
use App\Modules\Core\Services\TaxonomyRegistry;
use App\Support\Admin\NavigationGroup;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use UnitEnum;

/**
 * برچسب‌های دسته‌بندی‌های مشترک (بخش ۱۸-۱۱).
 *
 * فهرست خود دسته‌بندی‌ها بسته است (`config/core.php`)؛ مدیر فقط برچسب‌های
 * هر کدام را می‌سازد، نام می‌گذارد، مرتب می‌کند یا حذف می‌کند. حذف برچسب،
 * آن را از همه محتواها برمی‌دارد ولی به خود محتوا دست نمی‌زند.
 */
final class TaxonomyPage extends Page
{
    public const ABILITY = 'admin.taxonomy.manage';

    protected static ?string $slug = 'taxonomy';

    protected static ?int $navigationSort = 90;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected string $view = 'core::filament.pages.taxonomy';

    public string $taxonomy = '';

    public ?int $editingId = null;

    /** @var array{name: string, slug: string, description: string} */
    public array $form = ['name' => '', 'slug' => '', 'description' => ''];

    public static function getNavigationLabel(): string
    {
        return 'دسته‌بندی‌ها';
    }

    public function getTitle(): string
    {
        return 'دسته‌بندی‌ها';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(TaxonomyRegistry $registry): void
    {
        $this->taxonomy = (string) array_key_first($registry->known());
    }

    /** @return array<string, string> */
    public function taxonomies(): array
    {
        return app(TaxonomyRegistry::class)->known();
    }

    /** @return list<TaxonomyTerm> */
    public function terms(): array
    {
        if (! array_key_exists($this->taxonomy, $this->taxonomies())) {
            return [];
        }

        return array_values(TaxonomyTerm::query()->ofTaxonomy($this->taxonomy)->orderBy('position')->orderBy('id')->get()->all());
    }

    public function choose(string $taxonomy): void
    {
        if (array_key_exists($taxonomy, $this->taxonomies())) {
            $this->taxonomy = $taxonomy;
            $this->cancel();
        }
    }

    public function edit(int $id): void
    {
        $term = $this->term($id);

        if ($term !== null) {
            $this->editingId = $term->id;
            $this->form = ['name' => $term->name, 'slug' => $term->slug, 'description' => (string) $term->description];
        }
    }

    public function cancel(): void
    {
        $this->editingId = null;
        $this->form = ['name' => '', 'slug' => '', 'description' => ''];
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:120'],
            'form.slug' => [
                'required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('taxonomy_terms', 'slug')->where('taxonomy', $this->taxonomy)->ignore($this->editingId),
            ],
            'form.description' => ['nullable', 'string', 'max:500'],
        ], attributes: ['form.name' => 'نام', 'form.slug' => 'نشانی', 'form.description' => 'توضیح'])['form'];

        $values = ['name' => trim($data['name']), 'slug' => $data['slug'], 'description' => trim((string) $data['description']) ?: null];
        $existing = $this->editingId === null ? null : $this->term($this->editingId);

        $term = $existing !== null
            ? tap($existing)->update($values)
            : TaxonomyTerm::query()->create([...$values, 'taxonomy' => $this->taxonomy,
                'position' => 1 + (int) TaxonomyTerm::query()->ofTaxonomy($this->taxonomy)->max('position')]);

        event(new TaxonomyTermChanged($existing !== null ? 'updated' : 'created', $term, $this->actorId()));
        Notification::make()->title($existing !== null ? 'برچسب ویرایش شد' : 'برچسب ساخته شد')->success()->send();
        $this->cancel();
    }

    public function move(int $id, bool $up): void
    {
        $ids = array_map(static fn (TaxonomyTerm $term): int => $term->id, $this->terms());
        $from = array_search($id, $ids, true);
        $to = $up ? $from - 1 : $from + 1;

        if ($from === false || ! isset($ids[$to])) {
            return;
        }

        [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

        DB::transaction(static function () use ($ids): void {
            foreach ($ids as $index => $termId) {
                TaxonomyTerm::query()->whereKey($termId)->update(['position' => $index + 1]);
            }
        });
    }

    public function delete(int $id): void
    {
        $term = $this->term($id);

        if ($term === null) {
            return;
        }

        $term->delete();
        event(new TaxonomyTermChanged('deleted', $term, $this->actorId()));
        Notification::make()->title('برچسب حذف شد')->success()->send();

        if ($this->editingId === $id) {
            $this->cancel();
        }
    }

    public function usage(int $id): int
    {
        return DB::table('taxonomables')->where('term_id', $id)->count();
    }

    private function term(int $id): ?TaxonomyTerm
    {
        return TaxonomyTerm::query()->ofTaxonomy($this->taxonomy)->find($id);
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
