<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\CourseReview;
use App\Modules\Courses\Domain\Enrollment;
use InvalidArgumentException;

/**
 * ثبت دیدگاه — فقط پس از تکمیل دوره، فقط یک‌بار به ازای هر ثبت‌نام.
 */
final readonly class SubmitCourseReview
{
    public function handle(Enrollment $enrollment, int $rating, ?string $comment): CourseReview
    {
        if ($enrollment->completed_at === null) {
            throw new InvalidArgumentException('فقط پس از تکمیل دوره می‌توانید دیدگاه ثبت کنید.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('امتیاز باید بین ۱ تا ۵ باشد.');
        }

        if (CourseReview::query()->where('enrollment_id', $enrollment->id)->exists()) {
            throw new InvalidArgumentException('برای این ثبت‌نام قبلاً دیدگاه ثبت شده است.');
        }

        return CourseReview::query()->create([
            'enrollment_id' => $enrollment->id,
            'rating' => $rating,
            'comment' => $comment,
        ]);
    }
}
