<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Tests;

use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Jobs\Services\SkillPassport;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketRating;
use App\Modules\Marketplace\Filament\Pages\MarketRatingsPage;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Money;
use App\Support\Taxonomy\TermData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * امتیاز دوطرفه، سابقه مجری و گذرنامه (بخش ۲۱-۶، DEC-85).
 */
final class RatingTest extends TestCase
{
    use MarketFixtures;
    use RefreshDatabase;

    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = $this->provider();
    }

    public function test_each_side_sees_the_other_rating_only_after_rating_or_after_the_reveal_period(): void
    {
        $client = $this->client();
        $contract = $this->completed($client);

        $this->actingAs($client)->get(route('market.contracts.show', $contract->uuid))->assertSee(route('market.contracts.rate', $contract->uuid));
        $this->actingAs($client)->post(route('market.contracts.rate', $contract->uuid), ['stars' => 5, 'comment' => 'نقشه صدا دقیق و به‌موقع تحویل شد.'])->assertSessionHasNoErrors();
        $this->assertSame(1, UserNotification::query()->where('user_id', $this->provider->id)->where('kind', 'marketplace.rated')->count());

        $this->actingAs($this->provider)->get(route('market.contracts.show', $contract->uuid))
            ->assertSee('طرف دیگر امتیازش را داده است')
            ->assertDontSee('نقشه صدا دقیق');

        $this->actingAs($this->provider)->post(route('market.contracts.rate', $contract->uuid), ['stars' => 4, 'comment' => 'پرداخت‌ها سر وقت بود.'])->assertSessionHasNoErrors();
        $this->actingAs($this->provider)->get(route('market.contracts.show', $contract->uuid))->assertSee('نقشه صدا دقیق');
        $this->actingAs($client)->get(route('market.contracts.show', $contract->uuid))->assertSee('پرداخت‌ها سر وقت بود.')->assertDontSee(route('market.contracts.rate', $contract->uuid));

        $this->actingAs($client)->post(route('market.contracts.rate', $contract->uuid), ['stars' => 1])->assertSessionHasErrors('rating');
        $this->assertSame(2, MarketRating::query()->count());

        $second = $this->client();
        $other = $this->completed($second);
        $this->actingAs($second)->post(route('market.contracts.rate', $other->uuid), ['stars' => 3])->assertSessionHasNoErrors();
        $this->actingAs($this->provider)->get(route('market.contracts.show', $other->uuid))->assertDontSee('امتیاز کارفرما به شما');

        Carbon::setTestNow(Carbon::now()->addDays(15));
        $this->actingAs($this->provider)->get(route('market.contracts.show', $other->uuid))->assertSee('امتیاز کارفرما به شما');
        Carbon::setTestNow();
    }

    public function test_only_ended_contracts_within_the_window_take_ratings_and_contact_details_are_refused(): void
    {
        $client = $this->client();
        $project = $this->publishedProject($client);
        $this->actingAs($this->provider)->post(route('market.bid.store', $project->id), $this->bidForm())->assertSessionHasNoErrors();
        $this->actingAs($client)->post(route('market.contracts.accept', MarketBid::query()->sole()->uuid))->assertSessionHasNoErrors();
        $open = MarketContract::query()->sole();

        $this->actingAs($client)->post(route('market.contracts.rate', $open->uuid), ['stars' => 5])->assertSessionHasErrors('rating');

        // لغو پیش از هر پرداخت: پولی جابه‌جا نشده و امتیازپذیر نیست.
        $this->actingAs($client)->post(route('market.contracts.cancel', $open->uuid))->assertSessionHasNoErrors();
        $this->actingAs($client)->post(route('market.contracts.rate', $open->uuid), ['stars' => 1])->assertSessionHasErrors('rating');

        $done = $this->completed($client);
        $this->actingAs($client)->post(route('market.contracts.rate', $done->uuid), ['stars' => 5, 'comment' => 'برای هماهنگی به ۰۹۱۲۳۴۵۶۷۸۹ پیام بدهید.'])->assertSessionHasErrors('rating');
        $this->actingAs($client)->post(route('market.contracts.rate', $done->uuid), ['stars' => 9])->assertSessionHasErrors('rating');

        Carbon::setTestNow(Carbon::now()->addDays(31));
        $this->actingAs($client)->post(route('market.contracts.rate', $done->uuid), ['stars' => 5])->assertSessionHasErrors('rating');
        Carbon::setTestNow();

        $this->assertSame(0, MarketRating::query()->count());
    }

    public function test_the_public_profile_shows_delivered_projects_and_the_average_from_three_ratings(): void
    {
        $profile = route('consulting.show', 'sara-ahmadi');
        $this->get($profile)->assertOk()->assertDontSee('در بازار پروژه فرابهداشت');

        $client = $this->client();

        foreach ([5, 4] as $stars) {
            $contract = $this->completed($client);
            $this->actingAs($client)->post(route('market.contracts.rate', $contract->uuid), ['stars' => $stars, 'comment' => 'کار تمیز، امتیاز '.$stars])->assertSessionHasNoErrors();
        }

        Carbon::setTestNow(Carbon::now()->addDays(15));
        $this->get($profile)->assertSee('در بازار پروژه فرابهداشت')->assertSee('۲ پروژه تحویل‌شده')->assertSee('میانگین از ۳ امتیاز نشان داده می‌شود');
        Carbon::setTestNow();

        $contract = $this->completed($client);
        $this->actingAs($client)->post(route('market.contracts.rate', $contract->uuid), ['stars' => 3, 'comment' => 'ناسزای زشت'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->provider)->post(route('market.contracts.rate', $contract->uuid), ['stars' => 5])->assertSessionHasNoErrors();

        $this->actingAs($this->admin(AdminRole::Content));
        Livewire::test(MarketRatingsPage::class)->assertSee('ناسزای زشت')->call('hide', MarketRating::query()->where('comment', 'ناسزای زشت')->value('id'));
        auth()->logout();

        Carbon::setTestNow(Carbon::now()->addDays(15));
        $this->get($profile)->assertSee('۳ پروژه تحویل‌شده')->assertSee('میانگین ۴ از ۵، از ۳ امتیاز کارفرما')->assertSee('کار تمیز، امتیاز 5')->assertDontSee('ناسزای زشت');
        $this->get(route('market.show', $this->publishedProject($client)->id))->assertSee('کارفرمای خوش‌حساب در فرابهداشت');
        Carbon::setTestNow();
    }

    public function test_delivered_projects_feed_the_skills_passport(): void
    {
        $this->completed($this->client());

        $sections = array_column($this->app->make(SkillPassport::class)->verified($this->provider->id), 'items', 'key');
        $this->assertSame('اندازه‌گیری صدا', $sections['market'][0]->title);
        $this->assertSame(['market-service:noise'], $sections['market'][0]->tags);

        $skills = array_map(static fn (TermData $term): string => $term->slug, $this->app->make(SkillPassport::class)->verifiedSkills($this->provider->id));
        $this->assertSame(['noise-measurement'], $skills);
    }

    public function test_every_market_service_maps_to_a_job_skill(): void
    {
        $map = (array) config('jobs.passport.tag_skills');

        foreach (array_keys((array) config('consulting.directory.services')) as $service) {
            $skill = $map['market-service:'.$service] ?? null;
            $this->assertIsString($skill, $service);
            $this->assertArrayHasKey($skill, (array) config('jobs.skills'), $service);
        }
    }

    /** پروژه تازه، پیشنهاد مجری، پذیرش و پرداخت و تأیید همه مرحله‌ها. */
    private function completed(User $client): MarketContract
    {
        $project = $this->publishedProject($client);
        $this->actingAs($this->provider)->post(route('market.bid.store', $project->id), $this->bidForm())->assertSessionHasNoErrors();
        $bid = MarketBid::query()->where('project_id', $project->id)->sole();
        $this->actingAs($client)->post(route('market.contracts.accept', $bid->uuid))->assertSessionHasNoErrors();
        $contract = MarketContract::query()->where('bid_id', $bid->id)->sole();
        app(CreditWalletManually::class)->handle($client->id, Money::toman(10_000_000), null, idempotencyKey: (string) Str::uuid7());

        foreach ($contract->milestones as $milestone) {
            $this->actingAs($client)->post(route('market.milestones.pay', $milestone->uuid), ['payment' => 'wallet'])->assertSessionHasNoErrors();
            $this->actingAs($this->provider)->post(route('market.milestones.deliver', $milestone->uuid), ['note' => 'کار این مرحله تحویل شد.'])->assertSessionHasNoErrors();
            $this->actingAs($client)->post(route('market.milestones.approve', $milestone->uuid))->assertSessionHasNoErrors();
        }

        $contract->refresh();
        $this->assertSame(ContractStatus::Completed, $contract->status);

        return $contract;
    }
}
