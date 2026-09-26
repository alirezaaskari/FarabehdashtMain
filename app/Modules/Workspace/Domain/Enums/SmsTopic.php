<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain\Enums;

/**
 * گروه اعلان‌هایی که پیامک هم می‌شوند (DEC-39).
 *
 * فقط خبرهای مهم پیامک می‌گیرند؛ بقیه فقط در صندوق اعلان می‌مانند. کاربر
 * هر گروه را جدا خاموش می‌کند. گروه‌بندی به‌جای تک‌تک کلیدها است تا صفحه
 * تنظیم چهار انتخاب روشن داشته باشد، نه بیست ردیف فنی.
 *
 * کلید تازه (مثل یادآور وبینار) فقط یک خط در {@see forKind()}
 * می‌خواهد.
 */
enum SmsTopic: string
{
    case Review = 'review';
    case Money = 'money';
    case Subscription = 'subscription';
    case Calibration = 'calibration';
    case Expert = 'expert';

    public static function forKind(string $kind): ?self
    {
        return match ($kind) {
            'profile.approved',
            'profile.rejected',
            'courses.course_published',
            'courses.course_rejected',
            'courses.course_changes_approved',
            'courses.course_changes_rejected',
            'commerce.product_published',
            'commerce.product_rejected',
            'commerce.product_versions_approved',
            'commerce.product_versions_rejected',
            'encyclopedia.article_published',
            'encyclopedia.article_returned',
            'expert.question_published',
            'expert.question_rejected',
            'expert.answer_approved',
            'expert.answer_rejected' => self::Review,

            // فقط واریز: برداشت همیشه کار خود کاربر است و همان لحظه روی صفحه می‌بیندش.
            'ledger.wallet_credited',
            'commerce.payout_paid',
            'commerce.payout_rejected' => self::Money,

            'monetization.subscription_ending',
            'monetization.team_seat_assigned' => self::Subscription,

            'projects.calibration_due' => self::Calibration,

            'expert.answer_published' => self::Expert,

            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Review => 'نتیجه بررسی مدیر',
            self::Money => 'واریز به کیف پول',
            self::Subscription => 'اشتراک حرفه‌ای',
            self::Calibration => 'یادآور کالیبراسیون تجهیز',
            self::Expert => 'پاسخ تازه به پرسش شما',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Review => 'تأیید یا رد نقش، دوره، محصول و نوشته دانشنامه.',
            self::Money => 'شارژ کیف پول، بازگشت وجه و تسویه.',
            self::Subscription => 'هفت روز مانده به پایان اشتراک و افزودن شما به تیم.',
            self::Calibration => 'نزدیک‌شدن پایان اعتبار کالیبراسیون تجهیزات دفترچه شما.',
            self::Expert => 'وقتی مشاوری به پرسش شما در «پرسش از متخصص» پاسخ می‌دهد.',
        };
    }
}
