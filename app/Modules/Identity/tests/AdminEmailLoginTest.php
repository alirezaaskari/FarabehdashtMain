<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Models\User;
use App\Modules\Identity\Mail\AdminLoginCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * ورود مدیر با کد ایمیلی — فقط مدیر، فقط ایمیل تأییدشده، بی‌آنکه فرم بگوید
 * کدام ایمیل مدیر است.
 */
final class AdminEmailLoginTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'admin@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_the_login_page_links_to_the_admin_email_login(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('identity.email.show'));
    }

    public function test_an_admin_signs_in_with_the_code_sent_to_their_email(): void
    {
        $admin = $this->admin();

        $this->post(route('identity.email.store'), ['email' => ' Admin@Example.com '])
            ->assertRedirect(route('identity.email.verify.show'));

        $this->get(route('identity.email.verify.show'))->assertOk()->assertSee(self::EMAIL);

        $this->post(route('identity.email.verify.store'), ['code' => $this->codeSent()])
            ->assertRedirect(route('identity.profiles'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_the_email_is_plain_text_with_the_code(): void
    {
        $mail = new AdminLoginCode('4821', 10);

        $mail->assertSeeInText('4821');
        $mail->assertSeeInText('۱۰ دقیقه');
    }

    public function test_a_wrong_code_does_not_sign_in(): void
    {
        $this->admin();
        $this->post(route('identity.email.store'), ['email' => self::EMAIL]);

        $this->post(route('identity.email.verify.store'), ['code' => $this->codeSent() === '0000' ? '1111' : '0000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_an_unknown_email_looks_the_same_but_sends_nothing(): void
    {
        $this->post(route('identity.email.store'), ['email' => 'nobody@example.com'])
            ->assertRedirect(route('identity.email.verify.show'));

        Mail::assertNothingSent();

        $this->post(route('identity.email.verify.store'), ['code' => '1234'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_regular_user_cannot_sign_in_by_email(): void
    {
        User::factory()->create(['email' => self::EMAIL, 'email_verified_at' => now()]);

        $this->post(route('identity.email.store'), ['email' => self::EMAIL]);

        Mail::assertNothingSent();
    }

    public function test_an_email_changed_on_the_account_page_is_no_longer_trusted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('identity.account.update'), ['name' => 'مدیر', 'email' => 'new@example.com']);
        auth()->logout();

        $this->post(route('identity.email.store'), ['email' => 'new@example.com']);

        Mail::assertNothingSent();
    }

    public function test_the_verify_page_needs_a_pending_email(): void
    {
        $this->get(route('identity.email.verify.show'))->assertRedirect(route('identity.email.show'));
    }

    public function test_make_admin_refuses_an_email_owned_by_another_account(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--email' => self::EMAIL])->assertFailed();
        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--email' => 'not-an-email'])->assertFailed();
    }

    private function admin(): User
    {
        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--email' => 'Admin@Example.com'])->assertSuccessful();

        $admin = User::query()->where('mobile', '09121234567')->sole();
        $this->assertSame(self::EMAIL, $admin->email);
        $this->assertNotNull($admin->email_verified_at);

        return $admin;
    }

    private function codeSent(): string
    {
        $code = null;

        Mail::assertSent(AdminLoginCode::class, static function (AdminLoginCode $mail) use (&$code): bool {
            $code = $mail->code;

            return $mail->hasTo(self::EMAIL);
        });

        return (string) $code;
    }
}
