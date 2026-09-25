<?php

declare(strict_types=1);

namespace App\Modules\Expert\Services;

use App\Models\User;
use App\Modules\Expert\Actions\SubmitAnswer;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertQuestion;

/**
 * چه کسی یک پرسش را می‌بیند (DEC-41).
 *
 * - پرسش تأییدشده و عمومی: همه.
 * - پرسش‌کننده: همیشه، حتی در انتظار یا ردشده.
 * - مشاور تأییدشده: هر پرسش تأییدشده، خصوصی هم.
 * - مدیر بازبین: همه، برای تصمیم.
 *
 * هر کس دیگری ۴۰۴ می‌گیرد، نه ۴۰۳: وجود پرسش خصوصی هم نباید لو برود.
 */
final readonly class QuestionAccess
{
    public const REVIEW_ABILITY = 'admin.content.review';

    public function canView(ExpertQuestion $question, ?User $viewer): bool
    {
        if ($question->isPublic()) {
            return true;
        }

        if ($viewer === null) {
            return false;
        }

        return $question->user_id === $viewer->getKey()
            || $viewer->can(self::REVIEW_ABILITY)
            || ($question->status === ReviewStatus::Published && $this->isAnswerer($viewer));
    }

    public function isAnswerer(?User $viewer): bool
    {
        return $viewer?->can(SubmitAnswer::ABILITY) === true;
    }
}
