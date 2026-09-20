<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Sms;

use App\Contracts\SmsSender;
use App\Support\Sms\SmsMessage;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * ملی‌پیامک — ارسال با **الگو** از خط خدماتی.
 *
 * این درایور انتخاب درست برای رمز یک‌بارمصرف است. خط عمومی به گوشی‌هایی که
 * پیامک تبلیغاتی را مسدود کرده‌اند نمی‌رسد؛ خط خدماتی می‌رسد. در عوض، متن
 * آزاد نمی‌پذیرد: فقط شناسه الگوی تأییدشده (`bodyId`) و مقدار متغیرها.
 *
 * نقطه پایانی و نام پارامترها از SDK رسمی خود ملی‌پیامک گرفته شده‌اند:
 *
 *     POST https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber
 *     username · password · text · to · bodyId
 *
 * برخلاف ارسال عمومی، پارامتر `from` ندارد — خط به خود الگو بسته است.
 *
 * جداکننده مقدارها از پیکربندی می‌آید و پیش‌فرضش `;` است. اگر الگوی شما
 * بیش از یک متغیر دارد، پیش از اتکا به آن یک ارسال واقعی را بیازمایید؛
 * این تنها چیزی در این کلاس است که از مستندات قطعی درنیامده.
 */
final readonly class MelipayamakPatternSmsSender implements SmsSender
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private Http $http,
        private LoggerInterface $logger,
        private array $config,
    ) {}

    public function send(string $mobile, SmsMessage $message): void
    {
        foreach (['username', 'password'] as $key) {
            if (blank($this->config[$key] ?? null)) {
                throw new RuntimeException("پیکربندی ملی‌پیامک ناقص است: {$key} تنظیم نشده.");
            }
        }

        $bodyId = $this->bodyIdFor($message);
        $separator = (string) ($this->config['separator'] ?? ';');

        $response = $this->http
            ->timeout((int) ($this->config['timeout'] ?? 10))
            ->asForm()
            ->post((string) $this->config['endpoint'], [
                'username' => $this->config['username'],
                'password' => $this->config['password'],
                'text' => $message->joinedValues($separator),
                'to' => $mobile,
                'bodyId' => $bodyId,
            ]);

        if ($response->failed()) {
            $this->fail($mobile, ['status' => $response->status()]);
        }

        $this->guardAgainstRejection($mobile, $response->json());
    }

    /**
     * شناسه الگوی تأییدشده برای این پیام.
     *
     * نبودنش استثنا می‌دهد و به خط عمومی برنمی‌گردد. برگشتن بی‌صدا یعنی
     * پیامک از خطی می‌رود که ممکن است اصلاً وجود نداشته باشد، و کاربر بدون
     * اینکه کسی بفهمد کد را دریافت نمی‌کند.
     */
    private function bodyIdFor(SmsMessage $message): string
    {
        if (! $message->usesTemplate()) {
            throw new RuntimeException(
                'این پیام الگو ندارد و از خط خدماتی قابل ارسال نیست. '
                .'یا پیام را با SmsMessage::template بسازید، یا درایور را روی خط عمومی بگذارید.',
            );
        }

        /** @var array<string, string|int|null> $patterns */
        $patterns = $this->config['patterns'] ?? [];
        $bodyId = $patterns[$message->template] ?? null;

        if (blank($bodyId)) {
            throw new RuntimeException(sprintf(
                'شناسه الگوی «%s» تنظیم نشده است. آن را در پنل ملی‌پیامک بسازید و شناسه‌اش را در .env بگذارید.',
                $message->template,
            ));
        }

        return (string) $bodyId;
    }

    /**
     * پاسخ ۲۰۰ لزوماً یعنی ارسال شد.
     *
     * سامانه خطا را داخل بدنه پاسخ برمی‌گرداند، نه در کد وضعیت HTTP. اگر
     * این را بررسی نکنیم، شکست ارسالِ رمز ورود کاملاً بی‌صدا می‌ماند — بدترین
     * حالت ممکن، چون کاربر فقط می‌بیند «کد نیامد» و هیچ ردی در لاگ نیست.
     *
     * شکل پاسخ بین حساب‌ها یکسان نیست، پس فقط وقتی قضاوت می‌کنیم که
     * `RetStatus` واقعاً آمده باشد؛ وگرنه بدنه را لاگ می‌کنیم و رد می‌شویم.
     */
    private function guardAgainstRejection(string $mobile, mixed $body): void
    {
        if (! is_array($body) || ! array_key_exists('RetStatus', $body)) {
            $this->logger->info('پاسخ ملی‌پیامک شکل شناخته‌شده‌ای نداشت.', ['mobile' => $mobile]);

            return;
        }

        if ((int) $body['RetStatus'] === 1) {
            return;
        }

        $this->fail($mobile, [
            'ret_status' => $body['RetStatus'],
            'message' => $body['StrRetStatus'] ?? null,
            'value' => $body['Value'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function fail(string $mobile, array $context): void
    {
        // شماره موبایل در لاگ می‌ماند چون بدون آن عیب‌یابی ممکن نیست، ولی
        // متن پیام و کد هرگز نوشته نمی‌شوند.
        $this->logger->error('ارسال پیامک با الگو ناموفق بود.', ['mobile' => $mobile, ...$context]);

        throw new RuntimeException('ارسال پیامک ناموفق بود.');
    }
}
