<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Tests;

use App\Contracts\PaymentGateway;
use App\Contracts\WalletStatementReader;
use App\Models\User;
use App\Modules\ExamPrep\Domain\Enums\AttemptMode;
use App\Modules\ExamPrep\Domain\Enums\PurchaseStatus;
use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Modules\ExamPrep\Domain\PrepAttempt;
use App\Modules\ExamPrep\Domain\PrepChoice;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Support\Money;
use App\Support\Payments\FakeZarinPalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** خرید بسته، نمونه رایگان، تمرین، آزمون زمان‌دار و کارنامه (بخش ۱۸-۷). */
final class ExamPrepFlowTest extends TestCase
{
    use ExamFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeZarinPalGateway);
    }

    public function test_the_catalog_and_pack_page_list_published_packs_only(): void
    {
        $pack = $this->pack();
        $draft = $this->pack(['slug' => 'draft-pack', 'title' => 'بسته پیش‌نویس'], publish: false);

        $this->get(route('exam_prep.index'))
            ->assertOk()
            ->assertSee($pack->title)
            ->assertDontSee($draft->title);

        $this->get(route('exam_prep.show', $pack->slug))->assertOk()->assertSee('۱۹۰٬۰۰۰');
        $this->get(route('exam_prep.show', $draft->slug))->assertNotFound();
    }

    public function test_no_page_promises_a_pass(): void
    {
        $pack = $this->pack();
        $user = User::factory()->create();

        foreach ([route('exam_prep.index'), route('exam_prep.show', $pack->slug)] as $url) {
            $this->get($url)->assertOk()->assertDontSee('تضمین');
            $this->actingAs($user)->get($url)->assertOk()->assertDontSee('تضمین');
        }

        $this->actingAs($user)->get(route('exam_prep.mine'))->assertOk()->assertDontSee('تضمین');
    }

    public function test_the_free_sample_needs_no_purchase_but_practice_does(): void
    {
        $pack = $this->pack();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('exam_prep.start', $pack->slug), ['mode' => 'practice'])
            ->assertSessionHasErrors('start');

        $this->post(route('exam_prep.start', $pack->slug), ['mode' => 'sample'])->assertRedirect();

        $attempt = PrepAttempt::query()->sole();
        $this->assertSame(AttemptMode::Sample, $attempt->mode);
        $this->assertCount(2, $attempt->question_ids);
    }

    public function test_guests_are_sent_to_login_to_start(): void
    {
        $pack = $this->pack();

        $this->post(route('exam_prep.start', $pack->slug), ['mode' => 'sample'])->assertRedirect(route('login'));
    }

    public function test_paying_through_the_gateway_unlocks_practice_and_records_the_ledger_once(): void
    {
        $pack = $this->pack();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('exam_prep.purchase', $pack->slug))->assertRedirect();

        $purchase = PackPurchase::query()->sole();
        $this->assertSame(190_000, $purchase->price_toman);

        $params = ['Authority' => $purchase->gateway_authority, 'Status' => 'OK'];
        $this->get(route('exam_prep.purchase.callback', $params))->assertRedirect(route('exam_prep.show', $pack->slug));
        $this->get(route('exam_prep.purchase.callback', $params))->assertRedirect();

        $this->assertSame(PurchaseStatus::Paid, $purchase->fresh()?->status);
        $this->assertSame(1, LedgerTransaction::query()->where('kind', 'exam_prep.pack_paid')->count());

        $this->post(route('exam_prep.start', $pack->slug), ['mode' => 'practice'])->assertRedirect();
        $this->assertSame(AttemptMode::Practice, PrepAttempt::query()->sole()->mode);

        $this->post(route('exam_prep.purchase', $pack->slug))->assertSessionHasErrors('purchase');
    }

    public function test_the_wallet_pays_without_the_gateway(): void
    {
        $pack = $this->pack();
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(200_000), null);

        $this->actingAs($user)->post(route('exam_prep.purchase', $pack->slug), ['payment' => 'wallet'])
            ->assertRedirect(route('exam_prep.show', $pack->slug));

        $this->assertTrue(PackPurchase::query()->sole()->isPaid());
        $this->assertSame(10_000, $this->app->make(WalletStatementReader::class)->balanceOf($user->id)->toman);
    }

    public function test_an_insufficient_wallet_changes_nothing(): void
    {
        $pack = $this->pack();
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(10_000), null);

        $this->actingAs($user)->from(route('exam_prep.show', $pack->slug))
            ->post(route('exam_prep.purchase', $pack->slug), ['payment' => 'wallet'])
            ->assertSessionHasErrors('payment');

        $this->assertFalse(PackPurchase::query()->sole()->isPaid());
        $this->assertSame(10_000, $this->app->make(WalletStatementReader::class)->balanceOf($user->id)->toman);
    }

    public function test_switching_the_stream_off_closes_new_sales_but_keeps_access(): void
    {
        $pack = $this->pack();
        $buyer = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($buyer->id, Money::toman(200_000), null);
        $this->actingAs($buyer)->post(route('exam_prep.purchase', $pack->slug), ['payment' => 'wallet']);

        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::ExamPack, false);

        $this->post(route('exam_prep.start', $pack->slug), ['mode' => 'exam'])->assertRedirect();
        $this->assertSame(1, PrepAttempt::query()->count());

        $other = User::factory()->create();
        $this->actingAs($other)->post(route('exam_prep.purchase', $pack->slug));
        $this->assertSame(0, PackPurchase::query()->where('user_id', $other->id)->count());
        $this->get(route('exam_prep.show', $pack->slug))->assertOk()->assertSee('فروش این بسته در حال حاضر بسته است');
    }

    public function test_practice_answers_come_back_instantly_and_finish_with_a_scorecard(): void
    {
        $pack = $this->pack();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('exam_prep.start', $pack->slug), ['mode' => 'sample']);
        $attempt = PrepAttempt::query()->sole();
        [$first, $second] = $attempt->question_ids;

        $this->get(route('exam_prep.attempt', $attempt))->assertOk()->assertSee('ثبت پاسخ');

        $right = PrepChoice::query()->where('question_id', $first)->where('is_correct', true)->sole();
        $this->post(route('exam_prep.answer', $attempt), ['question' => $first, 'choice' => $right->id])
            ->assertRedirect(route('exam_prep.attempt', $attempt));
        $this->followRedirects($this->post(route('exam_prep.answer', $attempt), ['question' => $first, 'choice' => $right->id]))
            ->assertOk();
        $this->assertSame(1, $attempt->answers()->count(), 'پاسخ ثبت‌شده عوض نمی‌شود.');

        $wrong = PrepChoice::query()->where('question_id', $second)->where('is_correct', false)->firstOrFail();
        $response = $this->followRedirects($this->post(route('exam_prep.answer', $attempt), ['question' => $second, 'choice' => $wrong->id]));
        $response->assertSee('نادرست')->assertSee('توضیح پاسخ')->assertSee('دیدن کارنامه');

        $attempt->refresh();
        $this->assertTrue($attempt->isSubmitted());
        $this->assertSame(1, $attempt->correct_count);

        $this->get(route('exam_prep.result', $attempt))
            ->assertOk()
            ->assertSee('۵۰٪', false)
            ->assertSee('نیازمند مرور')
            ->assertSee('پاسخ‌های نادرست')
            ->assertSee('href="'.url('/encyclopedia').'"', false);
    }

    public function test_the_timed_exam_grades_on_submit_and_flags_a_late_form(): void
    {
        $pack = $this->pack();
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(200_000), null);
        $this->actingAs($user)->post(route('exam_prep.purchase', $pack->slug), ['payment' => 'wallet']);

        $this->post(route('exam_prep.start', $pack->slug), ['mode' => 'exam']);
        $attempt = PrepAttempt::query()->sole();
        $this->assertCount(4, $attempt->question_ids);
        $this->assertNotNull($attempt->deadline_at);

        $this->get(route('exam_prep.attempt', $attempt))->assertOk()->assertSee('data-exam-timer', false);

        $answers = [];
        foreach ($attempt->question_ids as $id) {
            $answers[$id] = PrepChoice::query()->where('question_id', $id)->where('is_correct', true)->value('id');
        }

        $this->travel(11)->minutes();

        $this->post(route('exam_prep.submit', $attempt), ['answers' => $answers])
            ->assertRedirect(route('exam_prep.result', $attempt));

        $attempt->refresh();
        $this->assertTrue($attempt->late);
        $this->assertSame(4, $attempt->correct_count);

        $this->post(route('exam_prep.submit', $attempt), ['answers' => []]);
        $this->assertSame(4, $attempt->fresh()?->correct_count, 'ارسال دوباره کارنامه را عوض نمی‌کند.');
    }

    public function test_opening_an_expired_exam_closes_it(): void
    {
        $pack = $this->pack();
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(200_000), null);
        $this->actingAs($user)->post(route('exam_prep.purchase', $pack->slug), ['payment' => 'wallet']);
        $this->post(route('exam_prep.start', $pack->slug), ['mode' => 'exam']);
        $attempt = PrepAttempt::query()->sole();

        $this->travel(2)->hours();

        $this->get(route('exam_prep.attempt', $attempt))->assertRedirect(route('exam_prep.result', $attempt));
        $this->assertTrue($attempt->fresh()?->isSubmitted());
    }

    public function test_attempts_are_private_to_their_owner(): void
    {
        $pack = $this->pack();
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('exam_prep.start', $pack->slug), ['mode' => 'sample']);
        $attempt = PrepAttempt::query()->sole();

        $this->actingAs(User::factory()->create())->get(route('exam_prep.attempt', $attempt))->assertNotFound();
    }
}
