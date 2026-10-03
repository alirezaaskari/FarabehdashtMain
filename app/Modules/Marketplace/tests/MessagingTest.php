<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Tests;

use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Marketplace\Domain\Enums\MessageStatus;
use App\Modules\Marketplace\Domain\Enums\ReviewMode;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketMessage;
use App\Modules\Marketplace\Filament\Pages\MarketMessagesPage;
use App\Modules\Marketplace\Services\MessagePolicy;
use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * گفت‌وگوی پیشنهاد و بررسی پیام (DEC-80): هیچ راه تماسی رد و بدل نمی‌شود،
 * دو حالت با کلید در پنل، و بسته‌شدن دسترسی پس از سقف اخطار.
 */
final class MessagingTest extends TestCase
{
    use MarketFixtures;
    use RefreshDatabase;

    /** @return iterable<string, array{string, bool}> */
    public static function messages(): iterable
    {
        yield 'plain question' => ['روز تعطیل هم برای اندازه‌گیری می‌آیید؟', false];
        yield 'short measurement' => ['تراز صدا حدود ۸۵ دسی‌بل است و شیفت شب ۹۲.', false];
        yield 'jalali date' => ['از ۱۴۰۵/۰۸/۱۲ کار را شروع می‌کنیم.', false];
        yield 'amount' => ['مرحله اول ۶۰۰۰۰۰۰ تومان می‌شود.', false];
        yield 'persian mobile' => ['شماره من ۰۹۱۲ ۳۴۵ ۶۷۸۹ است', true];
        yield 'latin digits with dashes' => ['call 0912-345-6789', true];
        yield 'worded digits' => ['صفر نه یک دو سه چهار پنج', true];
        yield 'email' => ['ایمیل بزنید lab [at] example [dot] com', true];
        yield 'link' => ['نمونه کارها در www.example.ir', true];
        yield 'handle' => ['در @noise_lab پیام بدهید', true];
        yield 'messenger word' => ['در تل‌گرام هم هستم؛ تلگرام بهتر است', true];
    }

    #[DataProvider('messages')]
    public function test_suspicious_messages_are_recognised(string $text, bool $suspicious): void
    {
        $this->assertSame($suspicious, $this->app->make(MessagePolicy::class)->flags($text) !== [], $text);
    }

    public function test_a_plain_message_arrives_and_a_suspicious_one_waits_for_the_admin(): void
    {
        [$bid, $client, $provider] = $this->bid();

        $this->actingAs($client)->post(route('market.bids.message', $bid->uuid), ['body' => 'روز تعطیل هم برای اندازه‌گیری می‌آیید؟'])->assertSessionHasNoErrors();
        $this->assertSame(MessageStatus::Delivered, MarketMessage::query()->sole()->status);
        $this->assertSame(1, UserNotification::query()->where('user_id', $provider->id)->where('kind', 'marketplace.message')->count());

        $this->actingAs($provider)->post(route('market.bids.message', $bid->uuid), ['body' => 'بله؛ برای هماهنگی به ۰۹۱۲۳۴۵۶۷۸۹ زنگ بزنید'])->assertSessionHasNoErrors();
        $held = MarketMessage::query()->latest('id')->firstOrFail();
        $this->assertSame(MessageStatus::Held, $held->status);
        $this->assertSame(0, UserNotification::query()->where('user_id', $client->id)->where('kind', 'marketplace.message')->count());

        $this->actingAs($client)->get(route('market.bids.show', $bid->uuid))->assertOk()
            ->assertSee('روز تعطیل')
            ->assertDontSee('۰۹۱۲۳۴۵۶۷۸۹')
            ->assertSee('data-page-help="bid-thread"', false);
        $this->actingAs($provider)->get(route('market.bids.show', $bid->uuid))->assertOk()->assertSee('در انتظار بررسی مدیر');

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('market.bids.show', $bid->uuid))->assertNotFound();
        $this->actingAs($stranger)->post(route('market.bids.message', $bid->uuid), ['body' => 'سلام'])->assertNotFound();

        $this->actingAs($this->admin(AdminRole::Content));
        Livewire::test(MarketMessagesPage::class)->assertSee('رشته رقم بلند')->call('reject', $held->id);

        $this->assertSame(MessageStatus::Rejected, $held->refresh()->status);
        $this->assertSame(1, UserNotification::query()->where('user_id', $provider->id)->where('kind', 'marketplace.message_rejected')->count());
        $this->actingAs($client)->get(route('market.bids.show', $bid->uuid))->assertDontSee('۰۹۱۲۳۴۵۶۷۸۹');
    }

    public function test_the_all_messages_mode_holds_everything_from_then_on(): void
    {
        [$bid, $client, $provider] = $this->bid();

        $this->actingAs($this->admin(AdminRole::Content));
        Livewire::test(MarketMessagesPage::class)->set('mode', ReviewMode::All->value)->call('saveSettings')->assertHasNoErrors();
        $this->assertSame(ReviewMode::All, $this->app->make(MessagePolicy::class)->mode());

        $this->actingAs($client)->post(route('market.bids.message', $bid->uuid), ['body' => 'روز تعطیل هم می‌آیید؟'])->assertSessionHasNoErrors();
        $message = MarketMessage::query()->sole();
        $this->assertSame(MessageStatus::Held, $message->status);

        $this->actingAs($this->admin(AdminRole::Content));
        Livewire::test(MarketMessagesPage::class)->call('approve', $message->id);
        $this->assertSame(MessageStatus::Delivered, $message->refresh()->status);
        $this->assertSame(1, UserNotification::query()->where('user_id', $provider->id)->where('kind', 'marketplace.message')->count());
    }

    public function test_the_word_list_is_editable_in_the_panel(): void
    {
        [$bid, $client] = $this->bid();

        $this->actingAs($this->admin(AdminRole::Content));
        Livewire::test(MarketMessagesPage::class)->set('words', "واتساپ\nبیرون از سایت")->call('saveSettings');

        $this->actingAs($client)->post(route('market.bids.message', $bid->uuid), ['body' => 'بیرون از سایت هماهنگ کنیم'])->assertSessionHasNoErrors();
        $this->assertSame(MessageStatus::Held, MarketMessage::query()->sole()->status);
    }

    public function test_strikes_block_bidding_and_posting_until_the_admin_reopens(): void
    {
        config(['marketplace.messages.strikes_limit' => 2]);
        [$bid, , $provider] = $this->bid();
        $admin = $this->admin(AdminRole::Content);

        foreach (['تلگرام پیام بدهید', 'واتساپ هم هستم'] as $body) {
            $this->actingAs($provider)->post(route('market.bids.message', $bid->uuid), ['body' => $body]);
            $this->actingAs($admin);
            Livewire::test(MarketMessagesPage::class)->call('reject', MarketMessage::query()->latest('id')->firstOrFail()->id);
        }

        $this->assertSame(1, UserNotification::query()->where('user_id', $provider->id)->where('kind', 'marketplace.access_blocked')->count());

        $another = $this->publishedProject();
        $this->actingAs($provider)->post(route('market.bid.store', $another->id), $this->bidForm())->assertSessionHasErrors('bid');
        $this->actingAs($provider)->post(route('market.client.store'), $this->projectForm())->assertSessionHasErrors();
        $this->actingAs($provider)->get(route('market.bids.mine'))->assertSee('دسترسی پیشنهاد بسته است');

        $this->actingAs($admin);
        Livewire::test(MarketMessagesPage::class)->assertSee('کاربر #'.$provider->id)->call('reopen', $provider->id)->assertSet('blocked', []);

        $this->actingAs($provider)->post(route('market.bid.store', $another->id), $this->bidForm())->assertSessionHasNoErrors();
    }

    public function test_the_messages_page_is_for_reviewers_only(): void
    {
        $this->actingAs($this->admin(AdminRole::Finance));
        $this->get(route('filament.fbh.pages.market-messages'))->assertForbidden();

        $this->actingAs($this->admin(AdminRole::Content));
        $this->get(route('filament.fbh.pages.market-messages'))->assertOk()->assertSee('فقط پیام مشکوک');
    }

    /** @return array{MarketBid, User, User} */
    private function bid(): array
    {
        $provider = $this->provider();
        $client = $this->client();
        $project = $this->publishedProject($client);
        $this->actingAs($provider)->post(route('market.bid.store', $project->id), $this->bidForm())->assertSessionHasNoErrors();

        return [MarketBid::query()->sole(), $client, $provider];
    }
}
