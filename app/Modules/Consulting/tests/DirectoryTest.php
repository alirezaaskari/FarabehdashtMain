<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Consulting\Actions\ReviewConsultantProfile;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\DirectoryContact;
use App\Modules\Consulting\Domain\Enums\ProviderKind;
use App\Modules\Consulting\Seo\DirectorySitemapSource;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Workspace\Domain\UserNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دایرکتوری خدمات تخصصی (بخش ۱۹-۵): صفحه آزمایشگاه، صفحه خدمت و شهر،
 * noindex زیر دو ارائه‌دهنده و درخواست تماس با اجازه نمایش شماره (DEC-58/59).
 */
final class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    private const BIO = 'آزمایشگاه دارای تجهیزات اندازه‌گیری صدا، روشنایی و نمونه‌برداری هوا با بیش از ده سال کار در کارخانه‌های استان.';

    public function test_a_laboratory_builds_a_page_that_waits_for_the_admin(): void
    {
        $lab = $this->provider(ProfileType::Laboratory, 'lab-one');
        $profile = ConsultantProfile::query()->where('user_id', $lab->id)->sole();

        $this->assertSame(ProviderKind::Laboratory, $profile->kind);
        $this->assertSame(['noise', 'lighting'], $profile->offerings);
        $this->get(route('consulting.labs.show', 'lab-one'))->assertOk()->assertSee('آزمایشگاه تأییدشده در فرابهداشت');
        // آزمایشگاه در فهرست مشاوران نمی‌آید و نشانی مشاور برایش باز نمی‌شود.
        $this->get(route('consulting.show', 'lab-one'))->assertNotFound();
        $this->get(route('consulting.index'))->assertDontSee('lab-one');
    }

    public function test_the_profile_needs_at_least_one_offering(): void
    {
        $user = $this->user(ProfileType::Laboratory);

        $this->actingAs($user)->post(route('consulting.profile.update'), $this->form('lab-x', ['offerings' => []]))
            ->assertSessionHasErrors('offerings');
        $this->actingAs($user)->post(route('consulting.profile.update'), $this->form('lab-x', ['offerings' => ['astrology']]))
            ->assertSessionHasErrors('offerings.0');
    }

    public function test_service_and_city_pages_list_providers_and_hide_thin_pages_from_search(): void
    {
        $this->provider(ProfileType::Laboratory, 'lab-one');

        $this->get(route('consulting.directory.service', 'noise'))->assertOk()->assertSee('lab-one')->assertSee('noindex', false);
        $this->get(route('consulting.directory.city', ['noise', 'kashan']))->assertOk()->assertSee('lab-one');
        $this->get(route('consulting.directory.service', 'heat'))->assertOk()->assertDontSee('lab-one');

        $this->provider(ProfileType::Consultant, 'sara-ahmadi', ['noise']);

        $this->get(route('consulting.directory.city', ['noise', 'kashan']))->assertOk()
            ->assertSee('lab-one')->assertSee('sara-ahmadi')->assertDontSee('noindex', false);

        $urls = array_map(static fn ($url) => $url->loc, iterator_to_array($this->app->make(DirectorySitemapSource::class)->sitemapUrls(), false));
        $this->assertContains(route('consulting.directory.city', ['noise', 'kashan']), $urls);
        $this->assertNotContains(route('consulting.directory.service', 'lighting'), $urls);
    }

    public function test_the_filter_redirects_to_the_canonical_address_and_unknown_keys_404(): void
    {
        $this->get(route('consulting.directory.index', ['service' => 'noise', 'city' => 'kashan']))
            ->assertRedirect(route('consulting.directory.city', ['noise', 'kashan']));
        $this->get(route('consulting.directory.index', ['service' => 'noise']))->assertRedirect(route('consulting.directory.service', 'noise'));
        $this->get(route('consulting.directory.index'))->assertOk()->assertSee('data-page-help="directory"', false);
        $this->get(route('consulting.directory.index', ['city' => 'kashan']))->assertOk()->assertSee('noindex', false);
        $this->get(route('consulting.directory.service', 'astrology'))->assertNotFound();
        $this->get(route('consulting.directory.city', ['noise', 'atlantis']))->assertNotFound();
    }

    public function test_a_contact_request_hides_the_mobile_unless_the_user_allows_it(): void
    {
        $lab = $this->provider(ProfileType::Laboratory, 'lab-one');
        $hidden = User::factory()->create(['mobile' => '09121111111']);
        $shared = User::factory()->create(['mobile' => '09122222222']);
        $message = 'اندازه‌گیری صدای دوازده ایستگاه در کارگاه ریخته‌گری را می‌خواهیم.';

        $this->post(route('consulting.contacts.store', 'lab-one'), ['message' => $message])->assertRedirect(route('login'));
        $this->actingAs($hidden)->post(route('consulting.contacts.store', 'lab-one'), ['message' => $message, 'service' => 'noise'])
            ->assertRedirect(route('consulting.contacts.mine'));
        $this->actingAs($shared)->post(route('consulting.contacts.store', 'lab-one'), ['message' => $message, 'share_mobile' => '1']);

        $this->assertSame(2, DirectoryContact::query()->count());
        $this->assertSame(2, UserNotification::query()->where('user_id', $lab->id)->where('kind', 'directory.contact_requested')->count());

        $this->actingAs($lab)->get(route('consulting.contacts.incoming'))->assertOk()
            ->assertSee('09122222222')->assertDontSee('09121111111');
    }

    public function test_the_lab_replies_once_and_only_its_own_contacts(): void
    {
        $lab = $this->provider(ProfileType::Laboratory, 'lab-one');
        $other = $this->provider(ProfileType::Laboratory, 'lab-two');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('consulting.contacts.store', 'lab-one'), ['message' => 'نمونه‌برداری گرد و غبار سیلیس برای سه ایستگاه کاری.']);
        $contact = DirectoryContact::query()->sole();

        $this->actingAs($other)->post(route('consulting.contacts.reply', $contact->uuid), ['reply' => 'پاسخ'])->assertSessionHasErrors('reply');
        $this->actingAs($lab)->post(route('consulting.contacts.reply', $contact->uuid), ['reply' => 'هفته بعد بازدید می‌کنیم.'])
            ->assertRedirect(route('consulting.contacts.incoming'));
        $this->actingAs($lab)->post(route('consulting.contacts.reply', $contact->uuid), ['reply' => 'دوباره'])->assertSessionHasErrors('reply');

        $this->assertSame('هفته بعد بازدید می‌کنیم.', $contact->fresh()?->reply);
        $this->assertSame(1, UserNotification::query()->where('user_id', $user->id)->where('kind', 'directory.contact_replied')->count());
        $this->actingAs($user)->get(route('consulting.contacts.mine'))->assertOk()->assertSee('هفته بعد بازدید می‌کنیم.');
    }

    public function test_a_consultant_cannot_receive_contact_requests(): void
    {
        $consultant = $this->provider(ProfileType::Consultant, 'sara-ahmadi');

        $this->actingAs(User::factory()->create())
            ->post(route('consulting.contacts.store', 'sara-ahmadi'), ['message' => 'درخواست تماس با مشاور از مسیر آزمایشگاه.'])
            ->assertNotFound();
        $this->actingAs($consultant)->get(route('consulting.contacts.incoming'))->assertForbidden();
    }

    /** @param  list<string>  $offerings */
    private function provider(ProfileType $type, string $slug, array $offerings = ['noise', 'lighting']): User
    {
        $user = $this->user($type);
        $this->actingAs($user)->post(route('consulting.profile.update'), $this->form($slug, ['offerings' => $offerings]))->assertSessionHasNoErrors();
        $profile = ConsultantProfile::query()->where('user_id', $user->id)->sole();
        $this->app->make(ReviewConsultantProfile::class)->approve($profile, $this->admin()->id);
        auth()->logout();

        return $user;
    }

    private function user(ProfileType $type): User
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->ofType($type)->active()->create();

        return $user->fresh() ?? $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(string $slug, array $overrides = []): array
    {
        return [
            'slug' => $slug,
            'display_name' => $slug,
            'headline' => 'اندازه‌گیری عوامل زیان‌آور محیط کار',
            'offerings' => ['noise', 'lighting'],
            'bio' => self::BIO,
            'province' => 'isfahan',
            'city' => 'kashan',
            ...$overrides,
        ];
    }

    private function admin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, AdminRole::Content);

        return $user->fresh() ?? $user;
    }
}
