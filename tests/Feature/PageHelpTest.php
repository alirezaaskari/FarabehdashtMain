<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Support\Help\HelpText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * راهنمای بخش‌ها: «این بخش به چه کار می‌آید؟» روی سایت، میزکار و پنل.
 */
final class PageHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_topic_explains_purpose_uses_and_an_example(): void
    {
        $topics = config('help');
        $panel = $topics['panel'];
        unset($topics['panel']);

        foreach ([...$topics, ...$panel] as $key => $help) {
            $this->assertNotSame('', trim((string) ($help['purpose'] ?? '')), $key);
            $this->assertNotEmpty($help['uses'] ?? [], $key);
            $this->assertNotSame('', trim((string) ($help['example'] ?? '')), $key);
        }
    }

    public function test_every_panel_topic_names_a_real_panel_page(): void
    {
        // کلیدی که با تغییر نام صفحه کهنه شود، بی‌صدا دیگر نشان داده نمی‌شود.
        foreach (array_keys(config('help.panel')) as $route) {
            $this->assertTrue(Route::has($route), $route);
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function publicPages(): iterable
    {
        yield 'tools' => ['tools.index', 'tools'];
        yield 'encyclopedia' => ['encyclopedia.index', 'encyclopedia'];
        yield 'chemicals' => ['chemicals.index', 'chemicals'];
        yield 'shop' => ['commerce.index', 'shop'];
        yield 'courses' => ['courses.index', 'courses'];
        yield 'sell' => ['commerce.sell', 'sell'];
        yield 'exam-prep' => ['exam_prep.index', 'exam-prep'];
        yield 'bundles' => ['bundles.index', 'bundles'];
        yield 'webinars' => ['webinars.index', 'webinars'];
        yield 'offline' => ['tools.offline', 'offline'];
        yield 'consultants' => ['consulting.index', 'consultants'];
        yield 'directory' => ['consulting.directory.index', 'directory'];
        yield 'pro' => ['monetization.plans', 'pro'];
        yield 'jobs' => ['jobs.index', 'jobs'];
        yield 'market' => ['market.index', 'market'];
    }

    #[DataProvider('publicPages')]
    public function test_a_public_section_explains_itself(string $route, string $topic): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertSee('data-page-help="'.$topic.'"', false)
            ->assertSee('این بخش به چه کار می‌آید؟');
    }

    /** @return iterable<string, array{string, string}> */
    public static function workspacePages(): iterable
    {
        yield 'dashboard' => ['workspace.dashboard', 'dashboard'];
        yield 'calculations' => ['tools.calculations.index', 'calculations'];
        yield 'projects' => ['projects.index', 'projects'];
        yield 'equipment' => ['projects.equipment.index', 'equipment'];
        yield 'calendar' => ['projects.calendar', 'calendar'];
        yield 'reports' => ['reports.index', 'reports'];
        yield 'my-courses' => ['courses.mine', 'my-courses'];
        yield 'purchases' => ['commerce.purchases', 'purchases'];
        yield 'account' => ['identity.account', 'account'];
        yield 'notifications' => ['workspace.notifications', 'notifications'];
        yield 'wallet' => ['workspace.wallet', 'wallet'];
        yield 'profiles' => ['identity.profiles', 'profiles'];
        yield 'my-exams' => ['exam_prep.mine', 'my-exams'];
        yield 'welcome' => ['identity.welcome', 'welcome'];
        yield 'client-projects' => ['market.client.index', 'client-projects'];
        yield 'provider-bids' => ['market.bids.mine', 'provider-bids'];
    }

    #[DataProvider('workspacePages')]
    public function test_a_workspace_section_explains_itself(string $route, string $topic): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route($route))
            ->assertOk()
            ->assertSee('data-page-help="'.$topic.'"', false);
    }

    /** @return iterable<string, array{ProfileType, string, string}> */
    public static function rolePages(): iterable
    {
        yield 'vendor-sales' => [ProfileType::Vendor, 'commerce.vendor.sales', 'vendor-sales'];
        yield 'product-create' => [ProfileType::Vendor, 'commerce.vendor.products.create', 'product-create'];
        yield 'instructor-sales' => [ProfileType::Instructor, 'courses.instructor.sales', 'instructor-sales'];
        yield 'course-create' => [ProfileType::Instructor, 'courses.instructor.courses.create', 'course-create'];
        yield 'writing' => [ProfileType::Writer, 'encyclopedia.writing.index', 'writing'];
    }

    #[DataProvider('rolePages')]
    public function test_a_role_section_explains_itself(ProfileType $type, string $route, string $topic): void
    {
        $user = User::factory()->create();
        $profile = $this->app->make(RequestProfileActivation::class)->handle($user, $type);
        $this->app->make(ReviewProfileRequest::class)->approve($profile, User::factory()->create());

        $this->actingAs($user->fresh() ?? $user)
            ->get(route($route))
            ->assertOk()
            ->assertSee('data-page-help="'.$topic.'"', false);
    }

    public function test_a_directory_listing_explains_itself(): void
    {
        $this->get(route('consulting.directory.service', 'noise'))
            ->assertOk()
            ->assertSee('data-page-help="directory"', false);
    }

    public function test_every_panel_page_with_a_topic_shows_it(): void
    {
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Super);
        $this->actingAs($admin->fresh() ?? $admin);

        foreach (config('help.panel') as $route => $help) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('data-page-help="'.$route.'"', false)
                ->assertSee($help['example']);
        }
    }

    public function test_measurements_in_help_copy_are_marked_as_numeric(): void
    {
        // همان قاعده بررسی دسترس‌پذیری CI: عدد لاتین در جمله فارسی فقط داخل [[…]].
        $copy = json_encode(config('help'), JSON_UNESCAPED_UNICODE) ?: '';

        $this->assertDoesNotMatchRegularExpression('/[0-9](?![^\[]*\]\])/u', (string) preg_replace('/filament\.fbh\.[\w.-]+/', '', $copy));

        $this->get(route('tools.index'))
            ->assertSee('<span data-numeric dir="ltr">50 ppm</span>', false)
            ->assertDontSee('[[', false);
    }

    public function test_help_markup_escapes_everything_else(): void
    {
        $this->assertSame(
            '&lt;b&gt; <span data-numeric dir="ltr">85 dB</span>',
            HelpText::render('<b> [[85 dB]]')->toHtml(),
        );
    }

    public function test_an_unknown_topic_renders_nothing(): void
    {
        $this->blade('<x-page-help topic="no-such-topic" />')
            ->assertDontSee('data-page-help', false);
    }
}
