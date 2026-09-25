<?php

declare(strict_types=1);

namespace App\Modules\Expert\Actions;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Expert\Domain\Enums\QuestionTopic;
use App\Modules\Expert\Domain\Enums\QuestionVisibility;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Expert\Events\QuestionAsked;
use App\Support\Entitlement\EntitlementReason;
use App\Support\Entitlement\Feature;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

/**
 * پرسش تازه، در انتظار تأیید مدیر.
 *
 * پرسیدن رایگان است (DEC-40). اولویت فقط وقتی ثبت می‌شود که کاربر واقعاً
 * مشترک باشد؛ باز بودن امکان برای همه (کلید اشتراک خاموش) اولویت نمی‌دهد،
 * وگرنه همه اولویت دارند و هیچ‌کس ندارد.
 */
final readonly class AskQuestion
{
    public function __construct(
        private EntitlementGate $entitlements,
        private Dispatcher $events,
    ) {}

    public function handle(
        User $asker,
        QuestionTopic $topic,
        QuestionVisibility $visibility,
        string $title,
        string $body,
    ): ExpertQuestion {
        $priority = $this->entitlements->decide($asker, Feature::PriorityQuestion)->reason === EntitlementReason::Subscribed;

        $question = ExpertQuestion::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $asker->getKey(),
            'topic' => $topic,
            'visibility' => $visibility,
            'title' => trim($title),
            'body' => trim($body),
            'status' => ReviewStatus::Pending,
            'priority' => $priority,
        ]);

        $this->events->dispatch(new QuestionAsked($question));

        return $question;
    }
}
