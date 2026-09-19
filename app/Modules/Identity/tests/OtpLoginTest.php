<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Contracts\SmsSender;
use App\Models\User;
use App\Modules\Identity\Domain\OtpCode;
use App\Modules\Identity\Services\Sms\FakeSmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private FakeSmsSender $sms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sms = new FakeSmsSender;
        $this->app->instance(SmsSender::class, $this->sms);
    }

    public function test_requesting_a_code_sends_one_sms_and_never_stores_the_raw_code(): void
    {
        $this->post(route('identity.login.store'), ['mobile' => '09121234567', 'terms' => '1'])
            ->assertRedirect(route('identity.verify.show'));

        $this->assertSame(1, $this->sms->count());

        $record = OtpCode::query()->sole();
        $code = $this->codeFromSms('09121234567');

        $this->assertNotSame($code, $record->code_hash, 'کد خام نباید ذخیره شود.');
        $this->assertTrue(password_verify($code, $record->code_hash));
    }

    public function test_a_correct_code_signs_the_user_in_and_creates_the_account(): void
    {
        $this->requestCode('09121234567');

        $this->post(route('identity.verify.store'), ['code' => $this->codeFromSms('09121234567')])
            ->assertRedirect(route('identity.profiles'));

        $this->assertAuthenticated();

        $user = User::query()->sole();
        $this->assertSame('09121234567', $user->mobile);
        $this->assertNotNull($user->mobile_verified_at);
        $this->assertCount(0, $user->profiles, 'حساب تازه هیچ پروفایل تجاری ندارد.');
    }

    public function test_the_mobile_number_is_normalised_before_anything_else(): void
    {
        $this->requestCode('+۹۸۹۱۲۱۲۳۴۵۶۷');

        $this->assertSame('09121234567', OtpCode::query()->sole()->mobile);
    }

    public function test_a_wrong_code_counts_an_attempt_and_says_how_many_are_left(): void
    {
        $this->requestCode('09121234567');

        $this->post(route('identity.verify.store'), ['code' => '0000'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, OtpCode::query()->sole()->attempts);
        $this->assertGuest();
    }

    public function test_a_code_burns_after_the_maximum_number_of_wrong_attempts(): void
    {
        config()->set('identity.otp.max_attempts', 2);
        $this->requestCode('09121234567');

        $correct = $this->codeFromSms('09121234567');
        $wrong = $correct === '0000' ? '1111' : '0000';

        $this->post(route('identity.verify.store'), ['code' => $wrong]);
        $this->post(route('identity.verify.store'), ['code' => $wrong]);

        // حتی کد درست هم پس از سوختن پذیرفته نمی‌شود.
        $this->post(route('identity.verify.store'), ['code' => $correct])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $this->requestCode('09121234567');

        $this->travel(config('identity.otp.ttl_seconds') + 5)->seconds();

        $this->post(route('identity.verify.store'), ['code' => $this->codeFromSms('09121234567')])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_a_used_code_cannot_be_used_twice(): void
    {
        $this->requestCode('09121234567');
        $code = $this->codeFromSms('09121234567');

        $this->post(route('identity.verify.store'), ['code' => $code]);
        $this->assertAuthenticated();

        $this->post(route('identity.signout'));
        $this->assertGuest();

        $this->withSession(['identity.pending_mobile' => '09121234567'])
            ->post(route('identity.verify.store'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_requesting_a_second_code_too_soon_is_refused(): void
    {
        $this->requestCode('09121234567');

        $this->post(route('identity.login.store'), ['mobile' => '09121234567', 'terms' => '1'])
            ->assertSessionHasErrors('mobile');

        $this->assertSame(1, $this->sms->count());
    }

    public function test_the_hourly_limit_per_mobile_is_enforced(): void
    {
        config()->set('identity.otp.max_requests_per_hour', 2);
        config()->set('identity.otp.resend_after_seconds', 0);

        $this->requestCode('09121234567');
        $this->requestCode('09121234567');

        $this->post(route('identity.login.store'), ['mobile' => '09121234567', 'terms' => '1'])
            ->assertSessionHasErrors('mobile');

        $this->assertSame(2, $this->sms->count());
    }

    public function test_a_suspended_account_cannot_sign_in(): void
    {
        User::factory()->suspended()->create(['mobile' => '09121234567']);

        $this->requestCode('09121234567');

        $this->post(route('identity.verify.store'), ['code' => $this->codeFromSms('09121234567')])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_the_terms_checkbox_is_required(): void
    {
        $this->post(route('identity.login.store'), ['mobile' => '09121234567'])
            ->assertSessionHasErrors('terms');

        $this->assertSame(0, $this->sms->count());
    }

    public function test_an_invalid_mobile_is_rejected_with_a_helpful_message(): void
    {
        $this->post(route('identity.login.store'), ['mobile' => '12345', 'terms' => '1'])
            ->assertSessionHasErrors('mobile');

        $this->assertSame(0, $this->sms->count());
    }

    private function requestCode(string $mobile): void
    {
        $this->post(route('identity.login.store'), ['mobile' => $mobile, 'terms' => '1']);
    }

    private function codeFromSms(string $mobile): string
    {
        $message = $this->sms->lastMessageTo($mobile) ?? '';

        preg_match('/(\d+)\s*$/u', $message, $matches);

        return $matches[1] ?? '';
    }
}
