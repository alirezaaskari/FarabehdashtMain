<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Consulting\Actions\ReviewConsultantProfile;
use App\Modules\Consulting\Domain\ConsultantDocument;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\Enums\ProfileReviewStatus;
use App\Modules\Consulting\Filament\Pages\ConsultantReviewPage;
use App\Modules\Consulting\Seo\ConsultantSitemapSource;
use App\Modules\Core\Domain\TaxonomyTerm;
use App\Modules\Expert\Domain\Enums\QuestionTopic;
use App\Modules\Expert\Domain\Enums\QuestionVisibility;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Identity\Actions\DeactivateProfile;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Regions\Regions;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * صفحه عمومی مشاور (بخش ۱۹-۲): تأیید مدیر پیش از هر نمایش، ماندن نسخه
 * قبلی تا تأیید ویرایش، مدرک خصوصی (DEC-50) و نبود تماس مستقیم (DEC-51).
 */
final class ConsultantProfileTest extends TestCase
{
    use RefreshDatabase;

    private const BIO = 'پانزده سال اندازه‌گیری عوامل زیان‌آور در صنایع فولاد و نساجی، ارزیابی مواجهه با صدا و گرد و غبار و طراحی برنامه حفاظت شنوایی.';

    public function test_only_a_consultant_can_open_the_editor(): void
    {
        $this->actingAs(User::factory()->create())->get(route('consulting.profile.edit'))->assertForbidden();
        $this->actingAs($this->consultant())->get(route('consulting.profile.edit'))
            ->assertOk()
            ->assertSee('data-page-help="consultant-profile"', false);
    }

    public function test_a_new_profile_waits_for_the_admin(): void
    {
        $consultant = $this->consultant();

        $this->actingAs($consultant)->post(route('consulting.profile.update'), $this->form())->assertRedirect(route('consulting.profile.edit'));

        $profile = ConsultantProfile::query()->sole();
        $this->assertSame(ProfileReviewStatus::Pending, $profile->status);
        $this->assertNull($profile->published_at);
        $this->assertSame('sara-ahmadi', $profile->slug);

        $this->get(route('consulting.show', 'sara-ahmadi'))->assertNotFound();
        $this->get(route('consulting.index'))->assertDontSee('سارا احمدی');
    }

    public function test_the_admin_publishes_and_the_page_shows_a_person_without_contact_details(): void
    {
        $noise = TaxonomyTerm::query()->create(['taxonomy' => 'health_domain', 'slug' => 'noise', 'name' => 'صدا']);
        $consultant = $this->consultant();
        $consultant->forceFill(['email' => 'sara@example.com'])->save();

        $this->actingAs($consultant)->post(route('consulting.profile.update'), $this->form(['domains' => [$noise->id]]));
        $profile = ConsultantProfile::query()->sole();

        $this->actingAs($this->admin());
        Livewire::test(ConsultantReviewPage::class)->call('approve', $profile->id)->assertSet('rows', []);

        $this->assertSame('consulting.profile_published', UserNotification::query()->where('user_id', $consultant->id)->sole()->kind);

        $this->get(route('consulting.show', 'sara-ahmadi'))
            ->assertOk()
            ->assertSee('سارا احمدی')
            ->assertSee('به اظهار مشاور')
            ->assertSee('"@type":"Person"', false)
            ->assertSee('"knowsAbout":["صدا"]', false)
            ->assertDontSee('hasCredential')
            ->assertDontSee('مدرک تأییدشده')
            ->assertDontSee('sara@example.com')
            ->assertDontSee($consultant->mobile);

        $this->get(route('consulting.index'))->assertSee('سارا احمدی');
        $this->get(route('consulting.index', ['domain' => 'noise']))->assertSee('سارا احمدی');
        $this->get(route('consulting.index', ['province' => 'fars']))->assertDontSee('سارا احمدی');
        $this->get(route('consulting.index', ['domain' => 'noise']))->assertSee('noindex', false);
    }

    public function test_an_edit_keeps_the_published_version_until_approved(): void
    {
        $consultant = $this->consultant();
        $profile = $this->published($consultant);

        $this->actingAs($consultant)->post(route('consulting.profile.update'), $this->form(['headline' => 'عنوان تازه که هنوز تأیید نشده', 'slug' => null]));

        $this->get(route('consulting.show', 'sara-ahmadi'))->assertOk()->assertDontSee('عنوان تازه که هنوز تأیید نشده');
        $this->assertSame(ProfileReviewStatus::Pending, $profile->fresh()?->status);

        $this->app->make(ReviewConsultantProfile::class)->approve($profile->fresh() ?? $profile, $this->admin()->id);
        $this->get(route('consulting.show', 'sara-ahmadi'))->assertSee('عنوان تازه که هنوز تأیید نشده');
    }

    public function test_the_address_is_fixed_after_the_first_publication(): void
    {
        $consultant = $this->consultant();
        $this->published($consultant);

        $this->actingAs($consultant)->post(route('consulting.profile.update'), $this->form(['slug' => 'another-name']))
            ->assertSessionHasErrors('slug');
    }

    public function test_a_rejection_needs_a_reason_and_keeps_the_draft(): void
    {
        $consultant = $this->consultant();
        $this->actingAs($consultant)->post(route('consulting.profile.update'), $this->form());
        $profile = ConsultantProfile::query()->sole();
        $review = $this->app->make(ReviewConsultantProfile::class);
        $admin = $this->admin();

        try {
            $review->reject($profile, $admin->id, ' ');
            $this->fail('رد بدون دلیل پذیرفته شد.');
        } catch (RuntimeException) {
        }

        $review->reject($profile, $admin->id, 'شماره تماس را از معرفی بردارید.');

        $this->actingAs($consultant)->get(route('consulting.profile.edit'))
            ->assertSee('شماره تماس را از معرفی بردارید.')
            ->assertSee('سارا احمدی');
    }

    public function test_a_city_must_belong_to_the_chosen_province(): void
    {
        $this->actingAs($this->consultant())
            ->post(route('consulting.profile.update'), $this->form(['province' => 'fars', 'city' => 'isfahan']))
            ->assertSessionHasErrors('city');
    }

    public function test_documents_are_private_to_the_consultant_and_the_admin(): void
    {
        Storage::fake('local');
        $consultant = $this->consultant();

        $this->actingAs($consultant)->post(route('consulting.documents.store'), [
            'document' => UploadedFile::fake()->create('degree.pdf', 200, 'application/pdf'),
        ])->assertRedirect(route('consulting.profile.edit'));

        $document = ConsultantDocument::query()->sole();
        Storage::disk('local')->assertExists($document->path);

        $this->actingAs($consultant)->get(route('consulting.documents.download', $document->uuid))->assertOk();
        $this->actingAs($this->admin())->get(route('consulting.documents.download', $document->uuid))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('consulting.documents.download', $document->uuid))->assertNotFound();
        $this->actingAs($this->consultant('دیگری'))->get(route('consulting.documents.download', $document->uuid))->assertNotFound();

        $this->actingAs($consultant)->delete(route('consulting.documents.destroy', $document->uuid))->assertRedirect();
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_the_page_hides_when_the_consultant_role_is_deactivated(): void
    {
        $consultant = $this->consultant();
        $this->published($consultant);

        $this->app->make(DeactivateProfile::class)->handle($consultant, ProfileType::Consultant);

        $this->get(route('consulting.show', 'sara-ahmadi'))->assertNotFound();
        $this->get(route('consulting.index'))->assertDontSee('سارا احمدی');
    }

    public function test_the_sitemap_lists_published_consultants(): void
    {
        $this->published($this->consultant());

        $this->get(route('consulting.index'))->assertOk();
        $this->assertContains(
            route('consulting.show', 'sara-ahmadi'),
            array_map(static fn ($url) => $url->loc, iterator_to_array($this->app->make(ConsultantSitemapSource::class)->sitemapUrls(), false)),
        );
    }

    public function test_published_answers_link_both_ways(): void
    {
        $consultant = $this->consultant();
        $this->published($consultant);

        $question = ExpertQuestion::query()->create([
            'uuid' => (string) Str::uuid7(), 'user_id' => User::factory()->create()->id,
            'topic' => QuestionTopic::cases()[0], 'visibility' => QuestionVisibility::Public,
            'title' => 'حفاظ شنوایی برای سالن پرس', 'body' => 'شرح پرسش', 'status' => ReviewStatus::Published,
            'published_at' => Carbon::now(),
        ]);
        ExpertAnswer::query()->create([
            'uuid' => (string) Str::uuid7(), 'question_id' => $question->id, 'user_id' => $consultant->id,
            'body' => 'متن پاسخ', 'status' => ReviewStatus::Published, 'published_at' => Carbon::now(),
        ]);

        $this->get(route('consulting.show', 'sara-ahmadi'))->assertSee('حفاظ شنوایی برای سالن پرس');
        $this->get(route('expert.show', $question->uuid))->assertSee(route('consulting.show', 'sara-ahmadi'), false);
    }

    public function test_city_keys_are_unique_across_provinces(): void
    {
        $regions = $this->app->make(Regions::class);
        $keys = [];

        foreach (array_keys($regions->provinces()) as $province) {
            $keys = [...$keys, ...array_keys($regions->cities($province))];
        }

        $this->assertCount(31, $regions->provinces());
        $this->assertSame($keys, array_values(array_unique($keys)));
    }

    /** @param  array<string, mixed>  $overrides */
    private function form(array $overrides = []): array
    {
        return array_filter([
            'slug' => 'sara-ahmadi',
            'display_name' => 'سارا احمدی',
            'headline' => 'کارشناس ارشد بهداشت حرفه‌ای',
            'bio' => self::BIO,
            'province' => 'isfahan',
            'city' => 'kashan',
            'experience' => 'پانزده سال در صنایع فولاد',
            'education' => 'کارشناسی ارشد بهداشت حرفه‌ای',
            ...$overrides,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function published(User $consultant): ConsultantProfile
    {
        $this->actingAs($consultant)->post(route('consulting.profile.update'), $this->form());
        $profile = ConsultantProfile::query()->where('user_id', $consultant->id)->sole();

        return $this->app->make(ReviewConsultantProfile::class)->approve($profile, $this->admin()->id);
    }

    private function consultant(string $name = 'مشاور آزمایشی'): User
    {
        $user = User::factory()->create(['name' => $name]);
        UserProfile::factory()->for($user)->ofType(ProfileType::Consultant)->active()->create();

        return $user->fresh() ?? $user;
    }

    private function admin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, AdminRole::Content);

        return $user->fresh() ?? $user;
    }
}
