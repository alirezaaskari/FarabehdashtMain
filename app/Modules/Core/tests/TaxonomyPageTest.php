<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Contracts\Taxonomy;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Core\Domain\AuditLog;
use App\Modules\Core\Domain\TaxonomyTerm;
use App\Modules\Core\Filament\Pages\TaxonomyPage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** پنل «دسته‌بندی‌ها» و قرارداد `Taxonomy` (بخش ۱۸-۱۱). */
final class TaxonomyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_creates_edits_orders_and_deletes_terms(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Super);

        $page = Livewire::actingAs($admin->fresh())->test(TaxonomyPage::class)
            ->call('choose', 'chemical_group')
            ->set('form.name', 'حلال‌ها')->set('form.slug', 'solvents')->call('save')
            ->assertNotified('برچسب ساخته شد')
            ->set('form.name', 'فلزات')->set('form.slug', 'metals')->call('save')
            ->set('form.name', 'تکراری')->set('form.slug', 'metals')->call('save')
            ->assertHasErrors('form.slug')
            ->call('cancel');

        $metals = TaxonomyTerm::query()->where('slug', 'metals')->sole();
        $page->call('move', $metals->id, true);
        $this->assertSame(['metals', 'solvents'], TaxonomyTerm::query()->where('taxonomy', 'chemical_group')->orderBy('position')->pluck('slug')->all());

        $page->call('edit', $metals->id)->set('form.name', 'فلزات سنگین')->call('save')->assertNotified('برچسب ویرایش شد');
        $this->assertSame('فلزات سنگین', $metals->refresh()->name);

        $page->call('delete', $metals->id)->assertNotified('برچسب حذف شد');
        $this->assertSame(['solvents'], TaxonomyTerm::query()->where('taxonomy', 'chemical_group')->pluck('slug')->all());
        $this->assertTrue(AuditLog::query()->where('action', 'taxonomy.term_deleted')->exists());
    }

    public function test_only_content_admins_open_it(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertFalse(TaxonomyPage::canAccess());
    }

    public function test_sync_only_touches_the_given_taxonomy(): void
    {
        $group = TaxonomyTerm::query()->create(['taxonomy' => 'chemical_group', 'slug' => 'solvents', 'name' => 'حلال‌ها']);
        $industry = TaxonomyTerm::query()->create(['taxonomy' => 'industry', 'slug' => 'oil', 'name' => 'نفت']);
        $taxonomy = $this->app->make(Taxonomy::class);

        $taxonomy->sync(User::class, 7, 'industry', [$industry->id]);
        $taxonomy->sync(User::class, 7, 'chemical_group', [$group->id, $industry->id]);

        $this->assertSame(['solvents'], array_map(fn ($t) => $t->slug, $taxonomy->termsOf(User::class, 7, 'chemical_group')));
        $this->assertSame(['oil'], array_map(fn ($t) => $t->slug, $taxonomy->termsOf(User::class, 7, 'industry')));
        $this->assertSame([7], $taxonomy->taggedIds(User::class, 'chemical_group', 'solvents'));

        $taxonomy->sync(User::class, 7, 'chemical_group', []);
        $this->assertSame([], $taxonomy->taggedIds(User::class, 'chemical_group', 'solvents'));
        $this->assertSame([7], $taxonomy->taggedIds(User::class, 'industry', 'oil'));
    }
}
