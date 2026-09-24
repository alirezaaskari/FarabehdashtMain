<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Identity\Events\UserSignedIn;
use App\Modules\Workspace\Actions\PublishLegalVersion;
use App\Modules\Workspace\Domain\Enums\LegalChange;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use App\Modules\Workspace\Domain\LegalAcceptance;
use App\Modules\Workspace\Domain\LegalVersion;
use App\Modules\Workspace\Http\Middleware\RequireLegalAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * صفحات حقوقی با نسخه و پذیرش دوباره (DEC-24).
 */
final class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unpublished_document_shows_an_empty_state_not_an_error(): void
    {
        $this->get(route('workspace.legal.show', 'terms'))
            ->assertOk()
            ->assertSee('این صفحه هنوز منتشر نشده است');
    }

    public function test_an_unknown_document_is_a_404(): void
    {
        $this->get('/legal/unknown')->assertNotFound();
    }

    public function test_a_published_version_renders_headings_and_paragraphs_as_plain_text(): void
    {
        $this->publish(LegalDocument::Terms, "## تعریف‌ها\n\nفرابهداشت یک فضای کاری است.\n\n<script>alert(1)</script>");

        $this->get(route('workspace.legal.show', 'terms'))
            ->assertOk()
            ->assertSee('<h2 class="mt-2 text-h3 text-ink">تعریف‌ها</h2>', escape: false)
            ->assertSee('فرابهداشت یک فضای کاری است.')
            ->assertDontSee('<script>alert(1)</script>', escape: false);
    }

    public function test_a_material_change_sends_a_signed_in_user_to_accept_before_any_workspace_page(): void
    {
        $user = User::factory()->create();
        $this->publish(LegalDocument::Terms, 'متن اول');

        $this->actingAs($user)
            ->get(route('workspace.wallet'))
            ->assertRedirect(route('workspace.legal.accept'));

        $this->get(route('workspace.legal.accept'))
            ->assertOk()
            ->assertSee('قوانین استفاده — نسخه ۱');

        $this->post(route('workspace.legal.accept.store'), ['agree' => '1'])
            ->assertRedirect(route('workspace.wallet'));

        $this->get(route('workspace.wallet'))->assertOk();

        $this->assertSame(1, LegalAcceptance::query()->where('user_id', $user->id)->count());
    }

    public function test_acceptance_needs_the_checkbox(): void
    {
        $this->publish(LegalDocument::Privacy, 'متن');

        $this->actingAs(User::factory()->create())
            ->post(route('workspace.legal.accept.store'))
            ->assertSessionHasErrors('agree');
    }

    public function test_public_pages_and_sign_out_are_never_blocked(): void
    {
        $this->publish(LegalDocument::Terms, 'متن');

        $this->actingAs(User::factory()->create());

        $this->get('/tools')->assertOk();
        $this->get(route('workspace.legal.show', 'terms'))->assertOk();
        $this->post(route('identity.signout'))->assertRedirect();
    }

    public function test_a_minor_change_after_acceptance_blocks_no_one(): void
    {
        $user = User::factory()->create();
        $this->publish(LegalDocument::Terms, 'متن اول');
        $this->actingAs($user)->post(route('workspace.legal.accept.store'), ['agree' => '1']);

        $this->publish(LegalDocument::Terms, 'متن اول، با غلط‌گیری', LegalChange::Minor);

        $this->get(route('workspace.wallet'))->assertOk();
    }

    public function test_a_new_material_version_asks_again_and_accepting_it_covers_the_older_ones(): void
    {
        $user = User::factory()->create();
        $this->publish(LegalDocument::Terms, 'متن اول');
        $this->actingAs($user)->post(route('workspace.legal.accept.store'), ['agree' => '1']);

        $this->publish(LegalDocument::Terms, 'متن دوم', LegalChange::Material, 'بند داوری اضافه شد.');

        $this->get(route('workspace.wallet'))->assertRedirect(route('workspace.legal.accept'));
        $this->get(route('workspace.legal.accept'))->assertSee('بند داوری اضافه شد.');
    }

    public function test_a_future_version_does_not_apply_before_its_effective_date(): void
    {
        $this->publish(LegalDocument::Terms, 'متن آینده', LegalChange::Material, 'تغییر', Carbon::now()->addWeek());

        $this->actingAs(User::factory()->create())->get(route('workspace.wallet'))->assertOk();
        $this->get(route('workspace.legal.show', 'terms'))->assertSee('این صفحه هنوز منتشر نشده است');
    }

    public function test_signing_in_with_the_terms_box_ticked_accepts_the_current_versions(): void
    {
        $user = User::factory()->create();
        $this->publish(LegalDocument::Terms, 'متن');
        $this->publish(LegalDocument::Privacy, 'متن');

        event(new UserSignedIn($user, accountWasCreated: true));

        $this->assertSame(2, LegalAcceptance::query()->where('user_id', $user->id)->count());
        $this->actingAs($user)->get(route('workspace.wallet'))->assertOk();
    }

    public function test_an_admin_viewing_as_the_user_is_neither_blocked_nor_allowed_to_accept(): void
    {
        $user = User::factory()->create();
        $this->publish(LegalDocument::Terms, 'متن');

        $this->actingAs($user)
            ->withSession([RequireLegalAcceptance::IMPERSONATION_SESSION_KEY => 1])
            ->get(route('workspace.wallet'))
            ->assertOk();

        $this->post(route('workspace.legal.accept.store'), ['agree' => '1'])->assertForbidden();
        $this->assertSame(0, LegalAcceptance::query()->count());
    }

    public function test_older_versions_keep_a_stable_noindexed_address(): void
    {
        $this->publish(LegalDocument::Privacy, 'نسخه یک');
        $this->publish(LegalDocument::Privacy, 'نسخه دو', LegalChange::Minor);

        $this->get(route('workspace.legal.version', ['privacy', 1]))
            ->assertOk()
            ->assertSee('نسخه یک')
            ->assertSee('این نسخه قدیمی است')
            ->assertSee('noindex', escape: false);

        $this->get(route('workspace.legal.show', 'privacy'))->assertSee('نسخه دو');
        $this->get(route('workspace.legal.version', ['privacy', 9]))->assertNotFound();
    }

    public function test_a_published_version_is_append_only(): void
    {
        $version = $this->publish(LegalDocument::Terms, 'متن');

        $this->expectException(RuntimeException::class);
        $version->update(['body' => 'متن دست‌کاری‌شده']);
    }

    public function test_the_first_version_of_an_acceptable_document_is_always_material(): void
    {
        $version = $this->publish(LegalDocument::Terms, 'متن', LegalChange::Minor);

        $this->assertSame(LegalChange::Material, $version->change);
    }

    public function test_a_material_change_needs_a_summary(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->make(PublishLegalVersion::class)->handle(LegalDocument::Terms, 'متن', LegalChange::Material, null, null, null);
    }

    public function test_only_super_admins_publish_legal_versions(): void
    {
        $page = '/'.config('admin.path').'/legal-documents';

        $this->actingAs($this->adminWith(AdminRole::Super))->get($page)->assertOk()->assertSee('انتشار نسخه تازه');
        $this->actingAs($this->adminWith(AdminRole::Content))->get($page)->assertForbidden();
    }

    public function test_the_footer_links_to_every_legal_page_and_the_status_page(): void
    {
        $this->get('/tools')
            ->assertSee(route('workspace.legal.show', 'terms'))
            ->assertSee(route('workspace.legal.show', 'privacy'))
            ->assertSee(route('workspace.status'));
    }

    private function publish(
        LegalDocument $document,
        string $body,
        LegalChange $change = LegalChange::Material,
        string $summary = 'نسخه تازه',
        ?Carbon $effectiveAt = null,
    ): LegalVersion {
        return $this->app->make(PublishLegalVersion::class)->handle($document, $body, $change, $summary, $effectiveAt, null);
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
