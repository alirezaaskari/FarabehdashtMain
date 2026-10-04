<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Contracts\SettingsStore;
use App\Modules\Marketplace\Domain\Enums\ReviewMode;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;

/**
 * قاعده بررسی پیام بازار (DEC-80): حالت «فقط مشکوک» یا «همه»، فهرست کلمه‌ها
 * و تشخیص پیام مشکوک.
 *
 * مشکوک یعنی رشته‌ای از رقم (فارسی، عربی یا لاتین، با فاصله و خط تیره) یا
 * رقم با حروف، ایمیل، لینک یا شناسه @، یا یکی از کلمه‌های فهرست. هدف گرفتن
 * راه تماس بیرون از سایت است، نه سانسور متن کاری؛ عدد کوتاه مثل «۸۵ دسی‌بل» مشکوک نیست.
 */
final readonly class MessagePolicy
{
    public const MODE_KEY = 'marketplace.messages.review_all';

    public const WORDS_KEY = 'marketplace.messages.words';

    private const DIGIT_WORDS = 'صفر|یک|دو|سه|چهار|پنج|شش|شیش|هفت|هشت|نه|ده|بیست|سی|چهل|پنجاه|شصت|هفتاد|هشتاد|نود|صد|zero|one|two|three|four|five|six|seven|eight|nine';

    public function __construct(
        private Container $container,
        private Repository $config,
    ) {}

    public function mode(): ReviewMode
    {
        return ReviewMode::tryFrom($this->settings()?->integer(self::MODE_KEY, 0) ?? 0) ?? ReviewMode::Suspicious;
    }

    /** @return list<string> */
    public function words(): array
    {
        $saved = $this->settings()?->text(self::WORDS_KEY) ?? '';
        $words = $saved === '' ? (array) $this->config->get('marketplace.messages.words', []) : preg_split('/[\n،,]+/u', $saved);

        return array_values(array_unique(array_filter(array_map(static fn (mixed $word): string => mb_strtolower(trim((string) $word)), $words ?: []))));
    }

    /**
     * دلیل‌های مشکوک بودن متن؛ خالی یعنی عادی.
     *
     * @return list<string>
     */
    public function flags(string $text): array
    {
        $normalized = mb_strtolower(strtr($text, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            "\u{200C}" => '',
        ]));
        $digits = max(4, (int) $this->config->get('marketplace.messages.digits_min', 7));
        $flags = [];

        // تاریخ («۱۴۰۴/۰۷/۱۲») و مبلغ («۸۰۰۰۰۰۰ تومان») شماره نیستند.
        $numbers = (string) preg_replace(['~\b\d{4}[/\-]\d{1,2}[/\-]\d{1,2}\b~u', '/\d[\d,٬\s]*\s*(?:تومان|ریال|میلیون|هزار)/u'], ' ', $normalized);

        if (preg_match('/(?:\d[\s\-\.\/_]*){'.$digits.',}/u', $numbers) === 1) {
            $flags[] = 'digits';
        }

        if (preg_match('/(?:(?:'.self::DIGIT_WORDS.')[\s\-،,]+){3,}(?:'.self::DIGIT_WORDS.')/u', $normalized) === 1) {
            $flags[] = 'worded_digits';
        }

        if (preg_match('/[a-z0-9._%+\-]+\s*(?:@|\[at\]|\(at\))\s*[a-z0-9.\-]+\s*(?:\.|\[dot\]|dot)\s*[a-z]{2,}/u', $normalized) === 1) {
            $flags[] = 'email';
        }

        if (preg_match('~(?:https?://|www\.|t\.me/|wa\.me/|\b[a-z0-9\-]+\.(?:ir|com|net|org|me|io)\b|@[a-z0-9_]{4,})~u', $normalized) === 1) {
            $flags[] = 'link';
        }

        foreach ($this->words() as $word) {
            if ($word !== '' && str_contains(str_replace("\u{200C}", '', $normalized), str_replace("\u{200C}", '', $word))) {
                $flags[] = 'word:'.$word;
                break;
            }
        }

        return $flags;
    }

    /** آیا این پیام تا تأیید مدیر نگه داشته شود. */
    public function holds(string $text): bool
    {
        return $this->mode() === ReviewMode::All || $this->flags($text) !== [];
    }

    private function settings(): ?SettingsStore
    {
        return $this->container->bound(SettingsStore::class) ? $this->container->make(SettingsStore::class) : null;
    }
}
