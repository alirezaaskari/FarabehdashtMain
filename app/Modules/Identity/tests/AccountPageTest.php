<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Models\User;
use App\Modules\Identity\Actions\SignOutOtherSessions;
use App\Modules\Identity\Events\AccountUpdated;
use App\Modules\Identity\Events\OtherSessionsSignedOut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * «حساب من» — نام، ایمیل و خروج از دستگاه‌های دیگر.
 */
final class AccountPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('identity.account'))->assertRedirect(route('login'));
    }

    public function test_the_page_shows_the_login_number_left_to_right(): void
    {
        $user = User::factory()->create(['name' => 'مریم', 'mobile' => '09121234567']);

        $this->actingAs($user)->get(route('identity.account'))
            ->assertOk()
            ->assertSee('value="مریم"', escape: false)
            ->assertSee('09121234567')
            ->assertSee('aria-current="page"', escape: false);
    }

    public function test_name_and_email_are_saved_and_audited_without_personal_data(): void
    {
        Event::fake([AccountUpdated::class]);
        $user = User::factory()->create(['email' => null]);

        $this->actingAs($user)
            ->put(route('identity.account.update'), ['name' => 'علی رضایی', 'email' => 'Ali@Example.com'])
            ->assertRedirect(route('identity.account'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('علی رضایی', $user->name);
        $this->assertSame('ali@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        Event::assertDispatched(AccountUpdated::class, function (AccountUpdated $event): bool {
            $entry = $event->auditEntry();
            $this->assertSame(['fields' => ['name', 'email']], $entry->after);
            $this->assertStringNotContainsString('ali@example.com', (string) json_encode($entry));

            return true;
        });
    }

    public function test_saving_unchanged_values_records_nothing(): void
    {
        Event::fake([AccountUpdated::class]);
        $user = User::factory()->create(['name' => 'مریم', 'email' => 'maryam@example.com']);

        $this->actingAs($user)->put(route('identity.account.update'), ['name' => 'مریم', 'email' => 'maryam@example.com']);

        Event::assertNotDispatched(AccountUpdated::class);
    }

    public function test_an_email_already_taken_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('identity.account.update'), ['name' => 'مریم', 'email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertNotSame('taken@example.com', $user->refresh()->email);
    }

    public function test_the_name_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('identity.account.update'), ['name' => '', 'email' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_signing_out_other_devices_closes_only_their_sessions(): void
    {
        config(['session.driver' => 'database']);
        Event::fake([OtherSessionsSignedOut::class]);

        $user = User::factory()->create(['remember_token' => 'old-token']);
        $other = User::factory()->create();

        foreach ([['current', $user], ['phone', $user], ['laptop', $user], ['someone', $other]] as [$id, $owner]) {
            DB::table('sessions')->insert([
                'id' => $id,
                'user_id' => $owner->id,
                'payload' => '',
                'last_activity' => now()->timestamp,
            ]);
        }

        $action = $this->app->make(SignOutOtherSessions::class);
        $this->assertSame(2, $action->otherSessionCount($user, 'current'));
        $this->assertSame(2, $action->handle($user, 'current'));

        $this->assertSame(['current', 'someone'], DB::table('sessions')->orderBy('id')->pluck('id')->all());
        $this->assertNotSame('old-token', $user->refresh()->remember_token);

        Event::assertDispatched(
            OtherSessionsSignedOut::class,
            static fn (OtherSessionsSignedOut $event): bool => $event->auditEntry()->subjectId === $user->id,
        );
    }

    public function test_the_button_works_even_when_sessions_are_not_in_the_database(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-token']);

        $this->actingAs($user)
            ->post(route('identity.account.sign-out-others'))
            ->assertRedirect(route('identity.account'))
            ->assertSessionHas('status');

        $this->assertNotSame('old-token', $user->refresh()->remember_token);
    }
}
