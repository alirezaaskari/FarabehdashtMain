<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Contracts\PaymentGateway;
use App\Contracts\WalletStatementReader;
use App\Models\User;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Monetization\Domain\Enums\InvitationStatus;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Team;
use App\Modules\Monetization\Domain\TeamFile;
use App\Modules\Monetization\Domain\TeamInvitation;
use App\Modules\Monetization\Domain\TeamPeriod;
use App\Modules\Monetization\Domain\TeamSeat;
use App\Modules\Monetization\Refunds\TeamRefunds;
use App\Modules\Monetization\Services\Payments\FakeSubscriptionGateway;
use App\Modules\Monetization\Services\SubscriptionReader;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * اشتراک تیم (بخش ۱۹-۶): قیمت پله‌ای (DEC-61)، دعوت با موبایل، مرز داده
 * اعضا و کتابخانه فقط برای فایل خود اعضا (DEC-62).
 */
final class TeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeSubscriptionGateway);
    }

    public function test_the_price_follows_the_seat_tiers_and_yearly_bills_ten_months(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('monetization.team.buy', ['name' => 'واحد HSE', 'seats' => 4, 'cycle' => 'monthly']))
            ->assertOk()->assertViewHas('total', fn (Money $total): bool => $total->toman === 1_000_000)
            ->assertSee('data-page-help="team-buy"', false);
        $this->actingAs($owner)->get(route('monetization.team.buy', ['seats' => 10, 'cycle' => 'yearly']))
            ->assertViewHas('total', fn (Money $total): bool => $total->toman === 22_000_000);
        // کمتر از کمینه به کمینه برمی‌گردد.
        $this->actingAs($owner)->get(route('monetization.team.buy', ['seats' => 1]))->assertViewHas('seats', 3);
        $this->actingAs($owner)->post(route('monetization.team.checkout'), ['name' => 'تیم', 'seats' => 2, 'cycle' => 'monthly'])
            ->assertSessionHasErrors('seats');
    }

    public function test_a_paid_team_opens_pro_for_the_owner_and_records_platform_revenue(): void
    {
        $owner = User::factory()->create();
        $period = $this->buy($owner, 3);

        $this->assertSame(PeriodStatus::Paid, $period->status);
        $this->assertSame(750_000, $period->price_toman);
        $this->assertSame(1, LedgerTransaction::query()->where('reference_id', $period->uuid)->count());
        $this->assertTrue($this->app->make(SubscriptionReader::class)->hasAccess($owner));

        // Callback تکراری اثر دوم ندارد.
        $this->get(route('monetization.team.callback', ['Authority' => $period->gateway_authority, 'Status' => 'OK']))->assertOk();
        $this->assertSame(1, LedgerTransaction::query()->where('reference_id', $period->uuid)->count());
    }

    public function test_paying_from_the_wallet_and_renewing_extends_from_the_current_end(): void
    {
        $owner = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($owner->id, Money::toman(2_000_000), null);

        $this->actingAs($owner)->post(route('monetization.team.checkout'), ['name' => 'تیم', 'seats' => 3, 'cycle' => 'monthly', 'payment' => 'wallet'])
            ->assertOk()->assertViewIs('monetization::team.paid');
        $firstEnd = Team::query()->sole()->ends_at;
        $this->actingAs($owner)->post(route('monetization.team.checkout'), ['name' => 'تیم', 'seats' => 4, 'cycle' => 'monthly', 'payment' => 'wallet'])->assertOk();

        $team = Team::query()->sole();
        $this->assertSame(4, $team->seat_count);
        $this->assertTrue($team->ends_at?->equalTo($firstEnd?->copy()->addMonth()));
        $this->assertSame(250_000, $this->app->make(WalletStatementReader::class)->balanceOf($owner->id)?->toman);
    }

    public function test_an_invited_member_accepts_and_gets_pro_without_exposing_their_data(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['mobile' => '09125550001']);
        $this->buy($owner, 3);

        $this->actingAs($owner)->post(route('monetization.team.invite'), ['mobile' => '۰۹۱۲۵۵۵۰۰۰۱'])->assertRedirect(route('monetization.team'));
        $invitation = TeamInvitation::query()->sole();
        $this->assertSame('09125550001', $invitation->mobile);
        $this->assertSame(1, UserNotification::query()->where('user_id', $member->id)->where('kind', 'monetization.team_invitation_invited')->count());
        $this->assertFalse($this->app->make(SubscriptionReader::class)->hasAccess($member));

        // دعوت فقط برای صاحب همان شماره است.
        $this->actingAs(User::factory()->create())->post(route('monetization.team.invitations.accept', $invitation->uuid))->assertSessionHasErrors('team');

        $this->actingAs($member)->get(route('monetization.team'))->assertOk()->assertSee('دعوت‌های رسیده');
        $this->actingAs($member)->post(route('monetization.team.invitations.accept', $invitation->uuid))->assertRedirect(route('monetization.team'));

        $this->assertSame(InvitationStatus::Accepted, $invitation->fresh()?->status);
        $this->assertTrue($this->app->make(SubscriptionReader::class)->hasAccess($member->fresh() ?? $member));

        // صاحب تیم عضو را می‌بیند ولی شماره یا داده او را نه.
        $this->actingAs($owner)->get(route('monetization.team'))->assertOk()->assertDontSee('09125550001');
    }

    public function test_seats_limit_invitations_and_the_owner_can_take_a_seat_back(): void
    {
        $owner = User::factory()->create(['mobile' => '09125550000']);
        $this->buy($owner, 3);

        $this->actingAs($owner)->post(route('monetization.team.invite'), ['mobile' => '09125550000'])->assertSessionHasErrors('mobile');
        $this->actingAs($owner)->post(route('monetization.team.invite'), ['mobile' => '09125550001']);
        $this->actingAs($owner)->post(route('monetization.team.invite'), ['mobile' => '09125550001'])->assertSessionHasErrors('mobile');
        $this->actingAs($owner)->post(route('monetization.team.invite'), ['mobile' => '09125550002']);
        $this->actingAs($owner)->post(route('monetization.team.invite'), ['mobile' => '09125550003'])->assertSessionHasErrors('mobile');

        $member = User::factory()->create(['mobile' => '09125550002']);
        $this->actingAs($member)->post(route('monetization.team.invitations.accept', TeamInvitation::query()->where('mobile', '09125550002')->sole()->uuid));
        $seat = TeamSeat::query()->sole();

        // کم کردن صندلی زیر اعضا و دعوت‌های باز پذیرفته نمی‌شود؛ حداقل ۳ است و ۳ گرفته شده.
        $this->actingAs($owner)->post(route('monetization.team.members.remove', $seat->id))->assertRedirect(route('monetization.team'));
        $this->assertFalse($seat->fresh()?->isActive());
        $this->assertFalse($this->app->make(SubscriptionReader::class)->hasAccess($member));

        // غریبه صندلی تیم دیگری را پس نمی‌گیرد.
        $this->actingAs(User::factory()->create())->post(route('monetization.team.members.remove', $seat->id))->assertNotFound();
    }

    public function test_a_member_leaves_and_access_ends_with_the_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['mobile' => '09125550001']);
        $this->buy($owner, 3);
        $this->actingAs($owner)->post(route('monetization.team.invite'), ['mobile' => '09125550001']);
        $this->actingAs($member)->post(route('monetization.team.invitations.accept', TeamInvitation::query()->sole()->uuid));

        $reader = $this->app->make(SubscriptionReader::class);
        $this->assertFalse($reader->hasAccess($member, Carbon::now()->addMonths(2)));
        $this->assertFalse($reader->hasAccess($owner, Carbon::now()->addMonths(2)));

        $this->actingAs($member)->post(route('monetization.team.leave'))->assertRedirect(route('monetization.team'));
        $this->assertFalse($reader->hasAccess($member));
    }

    public function test_the_library_is_only_for_the_team_and_keeps_files_private(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $member = User::factory()->create(['mobile' => '09125550001']);
        $this->buy($owner, 3);
        $this->actingAs($owner)->post(route('monetization.team.invite'), ['mobile' => '09125550001']);
        $this->actingAs($member)->post(route('monetization.team.invitations.accept', TeamInvitation::query()->sole()->uuid));

        $this->actingAs($member)->post(route('monetization.team.library.store'), [
            'title' => 'قالب گزارش صدا',
            'file' => UploadedFile::fake()->create('noise.docx', 40, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])->assertRedirect(route('monetization.team.library'));
        $file = TeamFile::query()->sole();
        Storage::disk('local')->assertExists($file->path);

        $this->actingAs($member)->post(route('monetization.team.library.store'), [
            'title' => 'برنامه',
            'file' => UploadedFile::fake()->create('run.exe', 10),
        ])->assertSessionHasErrors('file');

        $this->actingAs($owner)->get(route('monetization.team.library'))->assertOk()->assertSee('قالب گزارش صدا');
        $this->actingAs($owner)->get(route('monetization.team.library.download', $file->uuid))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('monetization.team.library.download', $file->uuid))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('monetization.team.library'))->assertOk()->assertSee('کتابخانه تیم برای اعضای تیم است');

        $this->actingAs($owner)->post(route('monetization.team.library.destroy', $file->uuid))->assertRedirect(route('monetization.team.library'));
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_the_team_page_guides_a_user_without_a_team(): void
    {
        $this->actingAs(User::factory()->create())->get(route('monetization.team'))->assertOk()
            ->assertSee('هنوز تیمی ندارید')->assertSee('data-page-help="team"', false);
        $this->get(route('monetization.plans'))->assertOk()->assertSee('اشتراک برای تیم');
    }

    public function test_a_team_refund_pays_the_owner_and_ends_every_seat(): void
    {
        $owner = User::factory()->create();
        $period = $this->buy($owner, 3);

        $amount = $this->app->make(TeamRefunds::class)->refund($period->uuid, User::factory()->create()->id, 'تیم منحل شد');

        $this->assertSame($period->price_toman, $amount->toman);
        $this->assertSame($period->price_toman, $this->app->make(WalletStatementReader::class)->balanceOf($owner->id)->toman);
        $this->assertFalse(Team::query()->sole()->isCurrent());
    }

    private function buy(User $owner, int $seats): TeamPeriod
    {
        $this->actingAs($owner)->post(route('monetization.team.checkout'), ['name' => 'واحد HSE', 'seats' => $seats, 'cycle' => 'monthly'])->assertRedirect();
        $period = TeamPeriod::query()->latest('id')->firstOrFail();
        $this->get(route('monetization.team.callback', ['Authority' => $period->gateway_authority, 'Status' => 'OK']))->assertOk();

        return $period->refresh();
    }
}
