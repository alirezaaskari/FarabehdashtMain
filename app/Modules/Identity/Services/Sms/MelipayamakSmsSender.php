<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * ملی‌پیامک — سرویس پیامک ثبت‌شده در تصمیم‌های پروژه.
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

    public function send(string $mobile, string $message): void
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
                'text' => $message,
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
