<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ورود مدیر با ایمیل و رمز عبور — فقط مدیر، فقط ایمیل تأییدشده و رمز ثبت‌شده
 * از خط فرمان، بی‌آنکه فرم بگوید کدام ایمیل مدیر است.
 */
final class AdminEmailLoginTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'admin@example.com';

    private const PASSWORD = 'a-long-admin-passphrase';

    public function test_the_login_page_links_to_the_admin_email_login(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('identity.email.show'));
        $this->get(route('identity.email.show'))->assertOk()->assertSee('رمز عبور');
    }

    public function test_an_admin_signs_in_with_email_and_password(): void
    {
        $admin = $this->admin();

        $this->post(route('identity.email.store'), ['email' => ' Admin@Example.com ', 'password' => self::PASSWORD])
            ->assertRedirect(route('identity.profiles'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame(self::PASSWORD, $admin->fresh()?->getAuthPassword());
    }

    public function test_a_wrong_password_and_an_unknown_email_get_the_same_answer(): void
    {
        $this->admin();

        $wrong = $this->from(route('identity.email.show'))
            ->post(route('identity.email.store'), ['email' => self::EMAIL, 'password' => 'not-the-password'])
            ->assertRedirect(route('identity.email.show'))
            ->assertSessionHasErrors('email');

        $unknown = $this->from(route('identity.email.show'))
            ->post(route('identity.email.store'), ['email' => 'nobody@example.com', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertSame(
            session('errors')?->first('email'),
            'ایمیل یا رمز عبور درست نیست.',
        );
        $this->assertSame($wrong->status(), $unknown->status());
        $this->assertGuest();
    }

    public function test_repeated_wrong_passwords_lock_the_email_even_for_the_right_one(): void
    {
        $this->admin();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('identity.email.store'), ['email' => self::EMAIL, 'password' => 'guess-guess-guess']);
        }

        $this->post(route('identity.email.store'), ['email' => self::EMAIL, 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['email' => 'تلاش‌های نادرست زیاد بود. ۱۵ دقیقه دیگر دوباره امتحان کنید.']);

        $this->assertGuest();
    }

    public function test_a_regular_user_with_a_password_cannot_sign_in_by_email(): void
    {
        User::factory()->create(['email' => self::EMAIL, 'email_verified_at' => now(), 'password' => self::PASSWORD]);

        $this->post(route('identity.email.store'), ['email' => self::EMAIL, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_admin_without_a_password_cannot_sign_in_by_email(): void
    {
        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--email' => self::EMAIL])
            ->expectsOutputToContain('--password')
            ->assertSuccessful();

        $this->post(route('identity.email.store'), ['email' => self::EMAIL, 'password' => ''])
            ->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_an_email_changed_on_the_account_page_is_no_longer_trusted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('identity.account.update'), ['name' => 'مدیر', 'email' => 'new@example.com']);
        auth()->logout();

        $this->post(route('identity.email.store'), ['email' => 'new@example.com', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_make_admin_rejects_a_short_or_mismatched_password(): void
    {
        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--password' => true])
            ->expectsQuestion('رمز عبور تازه (دست‌کم 12 نویسه)', 'short')
            ->assertFailed();

        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--password' => true])
            ->expectsQuestion('رمز عبور تازه (دست‌کم 12 نویسه)', self::PASSWORD)
            ->expectsQuestion('تکرار رمز عبور', 'something-else-entirely')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['mobile' => '09121234567']);
    }

    public function test_make_admin_refuses_an_email_owned_by_another_account(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--email' => self::EMAIL])->assertFailed();
        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--email' => 'not-an-email'])->assertFailed();
    }

    public function test_setting_the_password_is_audited_without_the_secret(): void
    {
        $admin = $this->admin();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.password_set',
            'subject_id' => $admin->getKey(),
            'before' => null,
            'after' => null,
        ]);
    }

    private function admin(): User
    {
        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--email' => 'Admin@Example.com', '--password' => true])
            ->expectsQuestion('رمز عبور تازه (دست‌کم 12 نویسه)', self::PASSWORD)
            ->expectsQuestion('تکرار رمز عبور', self::PASSWORD)
            ->expectsOutputToContain('ورود با ایمیل و رمز عبور برای این حساب فعال است')
            ->assertSuccessful();

        $admin = User::query()->where('mobile', '09121234567')->sole();
        $this->assertSame(self::EMAIL, $admin->email);
        $this->assertNotNull($admin->email_verified_at);

        return $admin;
    }
}
