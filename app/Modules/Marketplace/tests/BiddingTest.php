<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Tests;

use App\Contracts\TunableSource;
use App\Models\User;
use App\Modules\Marketplace\Domain\Enums\BidStatus;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketInvite;
use App\Modules\Workspace\Domain\Enums\SmsTopic;
use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * پیشنهاد مجری (بخش ۲۱-۳): فقط مشاور و آزمایشگاه تأییدشده (DEC-77)، حد
 * مرحله‌ها (DEC-79)، سقف ۳۰ روزه (DEC-86)، دعوت مستقیم (DEC-89) و پروژه
 * خصوصی (DEC-90).
 */
final class BiddingTest extends TestCase
{
    use MarketFixtures;
    use RefreshDatabase;

    public function test_a_verified_provider_bids_with_milestones_and_the_client_compares(): void
    {
        $provider = $this->provider();
        $client = $this->client();
        $project = $this->publishedProject($client);

        $this->assertSame(1, UserNotification::query()->where('user_id', $provider->id)->where('kind', 'marketplace.project_matched')->count());

        $this->actingAs($provider)->get(route('market.show', $project->id))->assertOk()->assertSee('ثبت پیشنهاد');
        $this->actingAs($provider)->get(route('market.bid.create', $project->id))->assertOk()->assertSee('data-page-help="bid-form"', false);
        $this->actingAs($provider)->post(route('market.bid.store', $project->id), $this->bidForm())->assertSessionHasNoErrors();

        $bid = MarketBid::query()->sole();
        $this->assertSame(BidStatus::Active, $bid->status);
        $this->assertSame(10_000_000, $bid->total_toman);
        $this->assertSame(12, $bid->total_days);
        $this->assertCount(2, $bid->milestones);
        $this->assertSame(1, UserNotification::query()->where('user_id', $client->id)->where('kind', 'marketplace.bid_received')->count());
        $this->assertSame(SmsTopic::Market, SmsTopic::forKind('marketplace.bid_received'));

        $this->actingAs($client)->get(route('market.client.show', $project->uuid))->assertOk()
            ->assertSee('سارا احمدی')
            ->assertSee('data-page-help="client-project-show"', false);
        $this->actingAs($client)->get(route('market.client.index'))->assertOk()->assertSee(route('market.client.show', $project->uuid), false);

        // ویرایش همان پیشنهاد سقف را مصرف نمی‌کند و ردیف تازه نمی‌سازد.
        $this->actingAs($provider)->post(route('market.bid.store', $project->id), $this->bidForm(['milestones' => [
            ['title' => 'اندازه‌گیری و گزارش', 'amount' => '9000000', 'days' => '10'],
        ]]))->assertSessionHasNoErrors();
        $this->assertSame(9_000_000, MarketBid::query()->sole()->total_toman);

        $this->actingAs($provider)->post(route('market.bids.withdraw', $bid->uuid))->assertRedirect(route('market.bids.mine'));
        $this->assertSame(BidStatus::Withdrawn, $bid->refresh()->status);
        $this->actingAs($client)->get(route('market.client.show', $project->uuid))->assertDontSee('سارا احمدی');
    }

    public function test_only_verified_providers_bid_and_never_on_their_own_project(): void
    {
        $project = $this->publishedProject();
        $plain = $this->client();

        $this->actingAs($plain)->get(route('market.bid.create', $project->id))->assertRedirect(route('market.show', $project->id))->assertSessionHasErrors('bid');
        $this->actingAs($plain)->post(route('market.bid.store', $project->id), $this->bidForm())->assertSessionHasErrors('bid');

        $provider = $this->provider();
        $own = $this->publishedProject($provider);
        $this->actingAs($provider)->post(route('market.bid.store', $own->id), $this->bidForm())->assertSessionHasErrors('bid');

        $this->assertSame(0, MarketBid::query()->count());
    }

    public function test_milestone_rules_and_contact_details_are_enforced(): void
    {
        $provider = $this->provider();
        $project = $this->publishedProject();
        $store = route('market.bid.store', $project->id);

        $this->actingAs($provider)->post($store, $this->bidForm(['milestones' => [['title' => 'کل کار', 'amount' => '300000', 'days' => '5']]]))
            ->assertSessionHasErrors('bid');

        config(['marketplace.bids.milestones_max' => 2]);
        $this->actingAs($provider)->post($store, $this->bidForm(['milestones' => [
            ['title' => 'مرحله یک', 'amount' => '1000000', 'days' => '2'],
            ['title' => 'مرحله دو', 'amount' => '1000000', 'days' => '2'],
            ['title' => 'مرحله سه', 'amount' => '1000000', 'days' => '2'],
        ]]))->assertSessionHasNoErrors();
        $this->assertCount(2, MarketBid::query()->sole()->milestones);

        $this->actingAs($provider)->post($store, $this->bidForm(['cover' => 'برای هماهنگی سریع‌تر در واتساپ پیام بدهید، با دستگاه کلاس یک اندازه می‌گیریم و گزارش می‌دهیم.']))
            ->assertSessionHasErrors('bid');
    }

    public function test_the_thirty_day_quota_limits_new_bids(): void
    {
        config(['marketplace.bids.per_30_days' => 1]);
        $provider = $this->provider();
        $first = $this->publishedProject();
        $second = $this->publishedProject();

        $this->actingAs($provider)->post(route('market.bid.store', $first->id), $this->bidForm())->assertSessionHasNoErrors();
        $this->actingAs($provider)->post(route('market.bid.store', $second->id), $this->bidForm())->assertSessionHasErrors('bid');

        Carbon::setTestNow(Carbon::now()->addDays(31));
        $second->forceFill(['bids_close_at' => Carbon::now()->addDays(3)])->save();
        $this->actingAs($provider)->post(route('market.bid.store', $second->id), $this->bidForm())->assertSessionHasNoErrors();
        Carbon::setTestNow();
    }

    public function test_a_private_project_is_seen_and_bid_on_only_by_invitees(): void
    {
        $invited = $this->provider();
        $other = $this->provider('ali-rad', 'علی راد');
        $client = $this->client();
        $project = $this->publishedProject($client, ['private' => '1']);

        $this->assertSame(0, UserNotification::query()->where('kind', 'marketplace.project_matched')->count());
        $this->actingAs($other)->get(route('market.show', $project->id))->assertNotFound();

        $this->actingAs($client)->get(route('market.invite.create', $invited->id))->assertOk()
            ->assertSee($project->title)
            ->assertSee('data-page-help="market-invite"', false);
        $this->actingAs($client)->post(route('market.invite.store', $invited->id), ['project' => $project->uuid])
            ->assertRedirect(route('market.client.show', $project->uuid));

        $this->assertSame(1, MarketInvite::query()->count());
        $this->assertSame(1, UserNotification::query()->where('user_id', $invited->id)->where('kind', 'marketplace.invited')->count());

        $this->actingAs($invited)->get(route('market.bids.mine'))->assertOk()->assertSee($project->title);
        $this->actingAs($invited)->get(route('market.show', $project->id))->assertOk();
        $this->actingAs($invited)->post(route('market.bid.store', $project->id), $this->bidForm())->assertSessionHasNoErrors();
        $this->actingAs($other)->post(route('market.bid.store', $project->id), $this->bidForm())->assertSessionHasErrors('bid');
    }

    public function test_a_directory_profile_offers_an_invite_and_only_listed_providers_are_invited(): void
    {
        $provider = $this->provider();
        $client = $this->client();
        $project = $this->publishedProject($client);

        $this->actingAs($client)->get(route('consulting.show', 'sara-ahmadi'))->assertOk()->assertSee(route('market.invite.create', $provider->id), false);
        $this->actingAs($provider)->get(route('consulting.show', 'sara-ahmadi'))->assertDontSee(route('market.invite.create', $provider->id), false);

        $stranger = User::factory()->create();
        $this->actingAs($client)->get(route('market.invite.create', $stranger->id))->assertNotFound();

        $someoneElse = $this->client();
        $this->actingAs($someoneElse)->post(route('market.invite.store', $provider->id), ['project' => $project->uuid])->assertSessionHasErrors('invite');
        $this->assertSame(0, MarketInvite::query()->count());
    }

    public function test_bid_settings_are_tunable_in_the_panel(): void
    {
        $keys = [];

        foreach ($this->app->tagged(TunableSource::TAG) as $source) {
            foreach ($source->tunables() as $tunable) {
                $keys[] = $tunable->key;
            }
        }

        foreach (['marketplace.bids.milestones_max', 'marketplace.bids.milestone_min_toman', 'marketplace.bids.per_30_days', 'marketplace.messages.strikes_limit'] as $key) {
            $this->assertContains($key, $keys);
        }
    }
}
