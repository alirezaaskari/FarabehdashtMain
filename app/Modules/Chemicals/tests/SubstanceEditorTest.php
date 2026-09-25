<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Chemicals\Actions\SaveSubstance;
use App\Modules\Chemicals\Admin\PendingSubstances;
use App\Modules\Chemicals\Domain\Enums\FactKind;
use App\Modules\Chemicals\Domain\Enums\LimitAuthority;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Filament\Resources\Substances\Pages\CreateSubstance;
use App\Modules\Chemicals\Filament\Resources\Substances\Pages\EditSubstance;
use App\Modules\Core\Domain\AuditLog;
use App\Modules\Core\Domain\ContentRevision;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * ویرایشگر بانک مواد: بدون آن ماده‌ای که با CSV می‌آمد هرگز حد مواجهه
 * نمی‌گرفت و در production منتشر نمی‌شد.
 */
final class SubstanceEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('fbh'));
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    /** @return array<string, mixed> */
    private function data(array $overrides = []): array
    {
        return array_replace([
            'name_fa' => 'تولوئن',
            'name_en' => 'Toluene',
            'cas_number' => '108-88-3',
            'slug' => 'toluene',
            'synonyms' => ['متیل بنزن'],
            'formula' => 'C7H8',
            'molar_mass' => 92.14,
            'physical_state' => 'مایع',
            'description' => null,
            'limits' => [[
                'authority' => 'acgih',
                'type' => 'twa',
                'value' => 20,
                'unit' => 'ppm',
                'note' => null,
                'reference_title' => 'ACGIH TLVs and BEIs',
                'reference_edition' => null,
                'reference_year' => 2024,
            ]],
            'routes' => ['استنشاق'],
            'symptoms' => ['سردرد', 'سرگیجه'],
            'protection' => [],
            'sampling_media' => null,
            'sampling_flow' => null,
            'analysis_method' => null,
            'method_number' => null,
        ], $overrides);
    }

    /**
     * فرم فهرست ساده را در حالت داخلی‌اش به شکل ردیف `text` نگه می‌دارد و هنگام
     * ذخیره دوباره به فهرست رشته برمی‌گرداند.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function form(array $data): array
    {
        foreach (array_keys(SaveSubstance::FACT_FIELDS) as $field) {
            $data[$field] = array_map(static fn (string $text): array => ['text' => $text], $data[$field]);
        }

        return $data;
    }

    public function test_a_content_admin_opens_the_editor_and_a_finance_admin_cannot(): void
    {
        $path = '/'.config('admin.path').'/substances';

        $this->actingAs($this->adminWith(AdminRole::Content))->get($path)->assertOk()->assertSee('ماده تازه');
        $this->actingAs($this->adminWith(AdminRole::Content))->get($path.'/create')->assertOk()->assertSee('حدود مواجهه');
        $this->actingAs($this->adminWith(AdminRole::Finance))->get($path)->assertForbidden();
    }

    public function test_creating_saves_a_draft_with_limits_facts_and_a_revision(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content));

        Livewire::test(CreateSubstance::class)
            ->fillForm($this->form($this->data()))
            ->call('create')
            ->assertHasNoFormErrors();

        $substance = Substance::query()->where('cas_number', '108-88-3')->sole();

        $this->assertSame(SubstanceStatus::Draft, $substance->status);
        $this->assertSame(LimitAuthority::Acgih, $substance->limits->sole()->authority);
        $this->assertSame(['سردرد', 'سرگیجه'], $substance->factsOf(FactKind::Symptom)->pluck('text')->all());
        $this->assertSame(['متیل بنزن'], $substance->synonyms->pluck('name')->all());
        $this->assertSame('ساخت از پنل', ContentRevision::query()->where('revisable_id', $substance->id)->sole()->reason);
        AuditLog::query()->where('action', 'chemicals.substance_created')->sole();
    }

    public function test_a_wrong_cas_check_digit_and_a_duplicate_limit_are_refused(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content));
        $limit = $this->data()['limits'][0];

        Livewire::test(CreateSubstance::class)
            ->fillForm($this->form($this->data(['cas_number' => '108-88-4', 'limits' => [$limit, $limit]])))
            ->call('create')
            ->assertHasFormErrors(['cas_number', 'limits']);

        $this->assertSame(0, Substance::query()->count());
    }

    public function test_publishing_from_the_editor_needs_a_versioned_source_for_every_limit(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content));
        $limit = $this->data()['limits'][0];

        $substance = $this->app->make(SaveSubstance::class)->handle(null, $this->data([
            'limits' => [[...$limit, 'reference_year' => null]],
        ]), null);

        $this->assertContains('انتشار ماده — تولوئن', array_map(
            static fn ($item): string => $item->title,
            iterator_to_array($this->app->make(PendingSubstances::class)->pendingItems(), false),
        ));

        Livewire::test(EditSubstance::class, ['record' => $substance->getRouteKey()])->callAction('publish');
        $this->assertSame(SubstanceStatus::Draft, $substance->refresh()->status);

        Livewire::test(EditSubstance::class, ['record' => $substance->getRouteKey()])
            ->fillForm($this->form($this->data()))
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('publish');

        $this->assertSame(SubstanceStatus::Published, $substance->refresh()->status);
        $this->assertSame(['ساخت از پنل', 'ویرایش از پنل', 'انتشار'], ContentRevision::query()
            ->where('revisable_id', $substance->id)->orderBy('version')->pluck('reason')->all());
    }

    public function test_the_address_of_a_published_substance_does_not_change(): void
    {
        $substance = $this->app->make(SaveSubstance::class)->handle(null, $this->data(), null);
        $substance->forceFill(['status' => SubstanceStatus::Published])->save();

        $saved = $this->app->make(SaveSubstance::class)->handle($substance, $this->data(['slug' => 'other']), null);

        $this->assertSame('toluene', $saved->slug);
    }

    public function test_the_edit_page_shows_what_was_saved(): void
    {
        $substance = $this->app->make(SaveSubstance::class)->handle(null, $this->data(), null);

        $this->actingAs($this->adminWith(AdminRole::Content));

        $page = Livewire::test(EditSubstance::class, ['record' => $substance->getRouteKey()])
            ->assertFormSet(['name_fa' => 'تولوئن', 'synonyms' => ['متیل بنزن']]);

        $this->assertSame(['سردرد', 'سرگیجه'], array_values(array_column($page->get('data.symptoms'), 'text')));
        $this->assertSame(['ACGIH TLVs and BEIs'], array_values(array_column($page->get('data.limits'), 'reference_title')));
    }
}
