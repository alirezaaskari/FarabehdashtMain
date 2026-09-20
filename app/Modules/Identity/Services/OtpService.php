<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Contracts\SmsSender;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Identity\Domain\OtpCode;
use App\Support\Sms\SmsMessage;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Carbon;

/**
 * تولید، ارسال و بررسی رمز یک‌بارمصرف.
 *
 * سه لایه محدودیت دارد تا هم کاربر اذیت نشود و هم شماره‌ها پویش نشوند:
 * فاصله تا ارسال بعدی · سقف ساعتی هر شماره · سقف ساعتی هر IP.
 */
final readonly class OtpService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private SmsSender $sms,
        private Hasher $hasher,
        private array $config,
    ) {}

    /**
     * درخواست کد تازه. در صورت موفقیت، ثانیه‌های باقی‌مانده تا ارسال بعدی را برمی‌گرداند.
     *
     * @throws OtpException
     */
    public function request(string $mobile, OtpPurpose $purpose, ?string $ip = null): int
    {
        $this->guardResendInterval($mobile, $purpose);
        $this->guardHourlyLimits($mobile, $ip);

        $code = $this->generateCode();

        OtpCode::query()->create([
            'mobile' => $mobile,
            'purpose' => $purpose->value,
            'code_hash' => $this->hasher->make($code),
            'expires_at' => now()->addSeconds($this->int('ttl_seconds')),
            'requested_ip' => $ip,
        ]);

        $this->sms->send($mobile, $this->message($code));

        return $this->int('resend_after_seconds');
    }

    /**
     * بررسی کد. در صورت درستی، کد مصرف‌شده علامت می‌خورد و دیگر قابل استفاده نیست.
     *
     * @throws OtpException
     */
    public function verify(string $mobile, OtpPurpose $purpose, string $code): void
    {
        $record = OtpCode::query()
            ->where('mobile', $mobile)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $record instanceof OtpCode) {
            throw OtpException::noPendingCode();
        }

        if ($record->isExpired()) {
            throw OtpException::expired();
        }

        $maxAttempts = $this->int('max_attempts');

        if (! $record->hasAttemptsLeft($maxAttempts)) {
            throw OtpException::burned();
        }

        if (! $this->hasher->check($code, $record->code_hash)) {
            $record->increment('attempts');

            $left = $maxAttempts - $record->attempts;

            throw $left > 0 ? OtpException::incorrect($left) : OtpException::burned();
        }

        $record->forceFill(['consumed_at' => now()])->save();
    }

    public function resendAfterSeconds(): int
    {
        return $this->int('resend_after_seconds');
    }

    /**
     * ثانیه‌های باقی‌مانده تا اجازه ارسال دوباره؛ صفر یعنی همین حالا مجاز است.
     * از زمان آخرین ارسال حساب می‌شود، نه از لحظه باز شدن صفحه.
     */
    public function secondsUntilResend(string $mobile, OtpPurpose $purpose): int
    {
        $last = $this->latestFor($mobile, $purpose);

        if (! $last instanceof OtpCode) {
            return 0;
        }

        $nextAllowedAt = $last->created_at->addSeconds($this->int('resend_after_seconds'));

        return $nextAllowedAt->isFuture()
            ? (int) ceil(Carbon::now()->diffInSeconds($nextAllowedAt))
            : 0;
    }

    public function codeLength(): int
    {
        return $this->int('length');
    }

    public function ttlSeconds(): int
    {
        return $this->int('ttl_seconds');
    }

    private function guardResendInterval(string $mobile, OtpPurpose $purpose): void
    {
        $left = $this->secondsUntilResend($mobile, $purpose);

        if ($left > 0) {
            throw OtpException::tooManyRequests($left);
        }
    }

    private function latestFor(string $mobile, OtpPurpose $purpose): ?OtpCode
    {
        return OtpCode::query()
            ->where('mobile', $mobile)
            ->where('purpose', $purpose->value)
            ->latest('id')
            ->first();
    }

    private function guardHourlyLimits(string $mobile, ?string $ip): void
    {
        $since = now()->subHour();

        $byMobile = OtpCode::query()->where('mobile', $mobile)->where('created_at', '>=', $since)->count();

        if ($byMobile >= $this->int('max_requests_per_hour')) {
            throw OtpException::hourlyLimitReached();
        }

        if ($ip === null) {
            return;
        }

        $byIp = OtpCode::query()->where('requested_ip', $ip)->where('created_at', '>=', $since)->count();

        if ($byIp >= $this->int('max_requests_per_ip_per_hour')) {
            throw OtpException::hourlyLimitReached();
        }
    }

    private function generateCode(): string
    {
        $length = $this->codeLength();

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * پیام رمز یک‌بارمصرف.
     *
     * هم متن کامل را می‌سازد و هم کد را جدا نگه می‌دارد. درایور خط عمومی و
     * درایور لاگ متن را می‌فرستند؛ درایور خط خدماتی فقط کد را، چون سامانه
     * پیامکی متن آزاد نمی‌پذیرد و خودش آن را داخل الگوی تأییدشده می‌گذارد.
     *
     * الگوی `otp` در پنل سرویس‌دهنده باید دقیقاً یک متغیر داشته باشد: کد.
     */
    private function message(string $code): SmsMessage
    {
        return SmsMessage::template(
            template: 'otp',
            values: [$code],
            text: sprintf('کد ورود شما به %s: %s', config('app.name'), $code),
        );
    }

    private function int(string $key): int
    {
        return (int) ($this->config[$key] ?? 0);
    }
}
