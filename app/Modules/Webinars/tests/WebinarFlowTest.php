<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Tests;

use App\Contracts\PaymentGateway;
use App\Contracts\WalletStatementReader;
use App\Models\User;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Webinars\Actions\ChangeWebinarStatus;
use App\Modules\Webinars\Actions\SendReminders;
use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Money;
use App\Support\Payments\FakeZarinPalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

/** رویداد و وبینار: ثبت‌نام، ظرفیت، پیوند ورود، یادآور و لغو (بخش ۱۸-۹). */
final class WebinarFlowTest extends TestCase
{
    use RefreshDatabase;
    use WebinarFixtures;

    private const string JOIN = 'https://www.skyroom.online/ch/fbh/noise';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeZarinPalGateway);
    }

    public function test_pages_list_published_webinars_and_never_show_the_join_link(): void
    {
        $webinar = $this->webinar();
        $draft = $this->webinar(['slug' => 'draft-webinar', 'title' => 'پیش‌نویس محرمانه'], publish: false);

        $this->get(route('webinars.index'))->assertOk()->assertSee($webinar->title)->assertDontSee($draft->title);
        $this->get(route('webinars.show', $draft->slug))->assertNotFound();

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('webinars.register', $webinar->slug), ['payment' => 'wallet']);

        $this->actingAs($user)->get(route('webinars.show', $webinar->slug))
            ->assertOk()
            ->assertSee('۵۰٬۰۰۰')
            ->assertDontSee('skyroom');
    }

    public function test_free_registration_is_confirmed_and_notifies(): void
    {
        $webinar = $this->webinar(['price' => '0']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('webinars.register', $webinar->slug))->assertRedirect(route('webinars.show', $webinar->slug));

        $registration = WebinarRegistration::query()->sole();
        $this->assertSame(RegistrationStatus::Confirmed, $registration->status);
        $this->assertSame(0, LedgerTransaction::query()->count());
        $this->assertTrue(UserNotification::query()->where('user_id', $user->id)->where('kind', 'webinars.registered')->exists());
    }

    public function test_paid_registration_by_wallet_and_by_gateway(): void
    {
        $webinar = $this->webinar(['capacity' => '5']);

        $walletUser = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($walletUser->id, Money::toman(60_000), null);
        $this->actingAs($walletUser)->post(route('webinars.register', $webinar->slug), ['payment' => 'wallet']);
        $this->assertSame(10_000, $this->app->make(WalletStatementReader::class)->balanceOf($walletUser->id)->toman);

        $gatewayUser = User::factory()->create();
        $this->actingAs($gatewayUser)->post(route('webinars.register', $webinar->slug))->assertRedirect();
        $registration = WebinarRegistration::query()->where('user_id', $gatewayUser->id)->sole();
        $callback = route('webinars.register.callback', ['Authority' => $registration->gateway_authority, 'Status' => 'OK']);
        $this->get($callback)->assertRedirect(route('webinars.show', $webinar->slug));
        $this->get($callback)->assertRedirect(route('webinars.show', $webinar->slug));

        $this->assertSame(2, WebinarRegistration::query()->where('status', RegistrationStatus::Confirmed)->count());
        $this->assertSame(1, LedgerTransaction::query()->where('reference_id', $registration->uuid)->count());
    }

    public function test_capacity_counts_held_seats_and_releases_abandoned_ones(): void
    {
        $webinar = $this->webinar(['price' => '0', 'capacity' => '1']);

        $this->actingAs(User::factory()->create())->post(route('webinars.register', $webinar->slug));
        $this->actingAs(User::factory()->create())->post(route('webinars.register', $webinar->slug))->assertSessionHasErrors('register');

        $paid = $this->webinar(['slug' => 'paid-one', 'capacity' => '1']);
        $this->actingAs(User::factory()->create())->post(route('webinars.register', $paid->slug));
        $this->actingAs(User::factory()->create())->post(route('webinars.register', $paid->slug))->assertSessionHasErrors('register');

        $this->travel(25)->minutes();
        $this->actingAs(User::factory()->create())->post(route('webinars.register', $paid->slug))->assertSessionDoesntHaveErrors();
    }

    public function test_the_join_link_opens_one_hour_before_start_for_registrants_only(): void
    {
        $webinar = $this->webinar(['price' => '0']);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('webinars.register', $webinar->slug));

        $this->actingAs($user)->get(route('webinars.join', $webinar->slug))->assertRedirect(route('webinars.show', $webinar->slug));

        $this->travelTo($webinar->starts_at->copy()->subMinutes(59));
        $this->actingAs($user)->get(route('webinars.join', $webinar->slug))
            ->assertRedirect(self::JOIN)
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->actingAs($user)->get(route('webinars.show', $webinar->slug))->assertSee('ورود به جلسه');

        $this->actingAs(User::factory()->create())->get(route('webinars.join', $webinar->slug))
            ->assertRedirect(route('webinars.show', $webinar->slug));

        $this->travelTo($webinar->endsAt()->copy()->addMinute());
        $this->actingAs($user)->get(route('webinars.join', $webinar->slug))->assertRedirect(route('webinars.show', $webinar->slug));
    }

    public function test_reminder_is_sent_once(): void
    {
        $webinar = $this->webinar(['price' => '0']);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('webinars.register', $webinar->slug));

        $remind = $this->app->make(SendReminders::class);
        $this->assertSame(0, $remind->handle());

        $this->travelTo($webinar->starts_at->copy()->subMinutes(100));
        $this->assertSame(1, $remind->handle());
        $this->assertSame(0, $remind->handle());
        $this->assertTrue(UserNotification::query()->where('user_id', $user->id)->where('kind', 'webinars.starting_soon')->exists());
    }

    public function test_cancel_refunds_paid_registrations_to_the_wallet(): void
    {
        $webinar = $this->webinar();
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(50_000), null);
        $this->actingAs($user)->post(route('webinars.register', $webinar->slug), ['payment' => 'wallet']);
        $this->assertSame(0, $this->app->make(WalletStatementReader::class)->balanceOf($user->id)->toman);

        $this->app->make(ChangeWebinarStatus::class)->cancel($webinar, User::factory()->create()->id);

        $this->assertSame(50_000, $this->app->make(WalletStatementReader::class)->balanceOf($user->id)->toman);
        $this->assertSame(RegistrationStatus::Cancelled, WebinarRegistration::query()->sole()->status);
        $this->assertTrue(UserNotification::query()->where('user_id', $user->id)->where('kind', 'webinars.cancelled')->exists());
    }

    public function test_closed_switch_blocks_paid_but_not_free_registration(): void
    {
        $paid = $this->webinar();
        $free = $this->webinar(['slug' => 'free-one', 'price' => '0']);
        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::EventWebinar, false);

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('webinars.register', $paid->slug))->assertSessionHasErrors('register');
        $this->actingAs($user)->post(route('webinars.register', $free->slug))->assertSessionDoesntHaveErrors();
    }

    public function test_the_join_link_must_be_https(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->webinar(['join_url' => 'http://example.com/room']);
    }

    public function test_starts_at_is_read_in_tehran_time(): void
    {
        $webinar = $this->webinar(['starts_at' => '2030-01-01T18:00'], publish: false);

        $this->assertSame('18:00', Carbon::parse($webinar->starts_at)->timezone('Asia/Tehran')->format('H:i'));
    }
}
