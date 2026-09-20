<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Contracts\SmsSender;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Services\OtpService;
use App\Modules\Identity\Services\Sms\FakeSmsSender;
use App\Modules\Identity\Services\Sms\MelipayamakPatternSmsSender;
use App\Modules\Identity\Services\Sms\MelipayamakSmsSender;
use App\Support\Sms\SmsMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http as HttpFacade;
use InvalidArgumentException;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\TestCase;

/**
 * ارسال رمز یک‌بارمصرف از **خط خدماتی با الگو**.
 *
 * خط عمومی به گوشی‌هایی که پیامک تبلیغاتی را مسدود کرده‌اند نمی‌رسد، و کاربر
 * بدون کد اصلاً نمی‌تواند وارد شود. پس مسیر واقعی تولید همین درایور است.
 */
final class SmsPatternTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function sender(array $overrides = []): MelipayamakPatternSmsSender
    {
        return new MelipayamakPatternSmsSender(
            $this->app->make(Http::class),
            new NullLogger,
            [
                'username' => 'user',
                'password' => 'secret',
                'endpoint' => self::ENDPOINT,
                'separator' => ';',
                'timeout' => 10,
                'patterns' => ['otp' => '54321'],
                ...$overrides,
            ],
        );
    }

    private function otpMessage(string $code = '4821'): SmsMessage
    {
        return SmsMessage::template('otp', [$code], "کد ورود شما به فرابهداشت: {$code}");
    }

    public function test_it_posts_only_the_values_not_the_whole_sentence(): void
    {
        // این مهم‌ترین تفاوت با خط عمومی است: سامانه متن آزاد نمی‌پذیرد و
        // خودش کد را داخل الگوی تأییدشده می‌گذارد.
        HttpFacade::fake([self::ENDPOINT => HttpFacade::response(['RetStatus' => 1])]);

        $this->sender()->send('09121234567', $this->otpMessage());

        HttpFacade::assertSent(function (Request $request): bool {
            $this->assertSame(self::ENDPOINT, $request->url());
            $this->assertSame('4821', $request['text']);
            $this->assertSame('09121234567', $request['to']);
            $this->assertSame('54321', $request['bodyId']);
            $this->assertSame('user', $request['username']);

            // خط به خود الگو بسته است؛ ارسال from خطای پیکربندی است.
            $this->assertArrayNotHasKey('from', $request->data());

            return true;
        });
    }

    public function test_several_values_are_joined_with_the_configured_separator(): void
    {
        HttpFacade::fake([self::ENDPOINT => HttpFacade::response(['RetStatus' => 1])]);

        $this->sender(['separator' => ';', 'patterns' => ['welcome' => '99']])->send(
            '09121234567',
            SmsMessage::template('welcome', ['علی', '4821'], 'خوش آمدید'),
        );

        HttpFacade::assertSent(fn (Request $request): bool => $request['text'] === 'علی;4821');
    }

    public function test_a_missing_pattern_id_throws_instead_of_falling_back(): void
    {
        // برگشتن بی‌صدا به خط عمومی یعنی پیامک از خطی می‌رود که شاید اصلاً
        // وجود ندارد، و کسی نمی‌فهمد چرا کد نرسید.
        HttpFacade::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('شناسه الگوی «otp» تنظیم نشده است');

        $this->sender(['patterns' => []])->send('09121234567', $this->otpMessage());
    }

    public function test_a_message_without_a_template_is_rejected(): void
    {
        HttpFacade::fake();

        $this->expectException(RuntimeException::class);

        $this->sender()->send('09121234567', SmsMessage::plain('یک متن آزاد'));
    }

    public function test_incomplete_credentials_are_reported_by_name(): void
    {
        HttpFacade::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('password تنظیم نشده');

        $this->sender(['password' => null])->send('09121234567', $this->otpMessage());
    }

    public function test_a_transport_failure_throws(): void
    {
        HttpFacade::fake([self::ENDPOINT => HttpFacade::response('', 500)]);

        $this->expectException(RuntimeException::class);

        $this->sender()->send('09121234567', $this->otpMessage());
    }

    public function test_a_rejection_inside_a_200_response_still_throws(): void
    {
        // سامانه خطا را در بدنه پاسخ برمی‌گرداند، نه در کد وضعیت HTTP. بدون
        // این بررسی، شکست ارسال رمز ورود کاملاً بی‌صدا می‌ماند.
        HttpFacade::fake([
            self::ENDPOINT => HttpFacade::response([
                'RetStatus' => 2,
                'StrRetStatus' => 'Credit is not enough',
                'Value' => '0',
            ]),
        ]);

        $this->expectException(RuntimeException::class);

        $this->sender()->send('09121234567', $this->otpMessage());
    }

    public function test_a_successful_response_passes(): void
    {
        HttpFacade::fake([
            self::ENDPOINT => HttpFacade::response(['RetStatus' => 1, 'Value' => '123456789']),
        ]);

        $this->sender()->send('09121234567', $this->otpMessage());

        HttpFacade::assertSentCount(1);
    }

    public function test_an_unknown_response_shape_does_not_throw(): void
    {
        // شکل پاسخ بین حساب‌ها یکسان نیست؛ نبودِ RetStatus نباید ارسالِ
        // احتمالاً موفق را شکست اعلام کند.
        HttpFacade::fake([self::ENDPOINT => HttpFacade::response('123456789')]);

        $this->sender()->send('09121234567', $this->otpMessage());

        HttpFacade::assertSentCount(1);
    }

    public function test_the_driver_is_selected_by_configuration(): void
    {
        config()->set('identity.sms.driver', 'melipayamak_pattern');
        $this->app->forgetInstance(SmsSender::class);

        $this->assertInstanceOf(MelipayamakPatternSmsSender::class, $this->app->make(SmsSender::class));

        config()->set('identity.sms.driver', 'melipayamak');
        $this->app->forgetInstance(SmsSender::class);

        $this->assertInstanceOf(MelipayamakSmsSender::class, $this->app->make(SmsSender::class));
    }

    public function test_the_general_line_driver_still_sends_the_whole_sentence(): void
    {
        $endpoint = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';

        HttpFacade::fake([$endpoint => HttpFacade::response(['RetStatus' => 1])]);

        (new MelipayamakSmsSender(
            $this->app->make(Http::class),
            new NullLogger,
            ['username' => 'user', 'password' => 'secret', 'from' => '5000', 'endpoint' => $endpoint, 'timeout' => 10],
        ))->send('09121234567', $this->otpMessage());

        HttpFacade::assertSent(function (Request $request): bool {
            $this->assertSame('کد ورود شما به فرابهداشت: 4821', $request['text']);
            $this->assertSame('5000', $request['from']);

            return true;
        });
    }

    public function test_the_otp_service_builds_a_template_message_carrying_the_code(): void
    {
        $sms = new FakeSmsSender;
        $this->app->instance(SmsSender::class, $sms);
        $this->app->forgetInstance(OtpService::class);

        $this->app->make(OtpService::class)->request('09121234567', OtpPurpose::Login, '127.0.0.1');

        $message = $sms->lastTo('09121234567');

        $this->assertNotNull($message);
        $this->assertTrue($message->usesTemplate());
        $this->assertSame('otp', $message->template);
        $this->assertCount(1, $message->values, 'الگوی otp باید دقیقاً یک متغیر داشته باشد: خود کد.');
        $this->assertStringContainsString($message->values[0], $message->text);
    }

    public function test_a_template_message_refuses_to_be_built_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SmsMessage::template('otp', [], 'متن');
    }

    public function test_a_template_message_refuses_an_empty_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SmsMessage::template('', ['4821'], 'متن');
    }
}
