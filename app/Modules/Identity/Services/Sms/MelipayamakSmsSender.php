<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Sms;

use App\Contracts\SmsSender;
use App\Support\Sms\SmsMessage;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * ملی‌پیامک — ارسال متن آزاد از **خط عمومی**.
 *
 * برای رمز یک‌بارمصرف معمولاً این درایور انتخاب درستی نیست: خط عمومی به
 * گوشی‌هایی که پیامک تبلیغاتی را مسدود کرده‌اند نمی‌رسد و کاربر بدون کد
 * می‌ماند. برای آن، `MelipayamakPatternSmsSender` را با خط خدماتی و الگو
 * به‌کار ببرید.
 *
 * شکست ارسال، استثنا می‌دهد تا جریان بالادست تصمیم بگیرد چه بگوید؛
 * اینجا هرگز پیام کاربرپسند ساخته نمی‌شود.
 *
 * @param  array{username: ?string, password: ?string, from: ?string, endpoint: string, timeout: int}  $config
 */
final readonly class MelipayamakSmsSender implements SmsSender
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private Http $http,
        private LoggerInterface $logger,
        private array $config,
    ) {}

    public function send(string $mobile, SmsMessage $message): void
    {
        foreach (['username', 'password', 'from'] as $key) {
            if (blank($this->config[$key] ?? null)) {
                throw new RuntimeException("پیکربندی ملی‌پیامک ناقص است: {$key} تنظیم نشده.");
            }
        }

        $response = $this->http
            ->timeout((int) ($this->config['timeout'] ?? 10))
            ->asForm()
            ->post((string) $this->config['endpoint'], [
                'username' => $this->config['username'],
                'password' => $this->config['password'],
                'from' => $this->config['from'],
                'to' => $mobile,
                'text' => $message->text,
                'isFlash' => 'false',
            ]);

        if ($response->failed()) {
            $this->logger->error('ارسال پیامک ناموفق بود.', [
                'mobile' => $mobile,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('ارسال پیامک ناموفق بود.');
        }
    }
}
