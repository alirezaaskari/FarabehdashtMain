<?php

declare(strict_types=1);

namespace App\Modules\Courses\Bundles;

use App\Contracts\BundleComponentSource;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Support\Bundles\BundleComponent;
use App\Support\Payments\PaymentSource;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * دوره به‌عنوان جزء بسته راه‌حل. اعطا یعنی ثبت‌نام پرداخت‌شده با مبلغ صفر و
 * منبع «بسته»؛ ثبت‌نام نیمه‌کاره قبلی همان ردیف است و به‌روز می‌شود.
 */
final readonly class CourseComponents implements BundleComponentSource
{
    public function kind(): string
    {
        return 'course';
    }

    public function label(): string
    {
        return 'دوره';
    }

    public function options(): array
    {
        return Course::query()->published()->orderBy('title')->get()
            ->reject(static fn (Course $course): bool => $course->isFree())
            ->map(fn (Course $course): BundleComponent => $this->component($course))
            ->values()
            ->all();
    }

    public function find(string $ref): ?BundleComponent
    {
        $course = Course::query()->published()->find((int) $ref);

        return $course === null ? null : $this->component($course);
    }

    public function owns(int $userId, string $ref): bool
    {
        return Enrollment::query()
            ->where('course_id', (int) $ref)
            ->where('student_user_id', $userId)
            ->where('status', EnrollmentStatus::Paid)
            ->exists();
    }

    public function grant(int $userId, string $ref, string $purchaseUuid): void
    {
        $values = [
            'status' => EnrollmentStatus::Paid,
            'price_toman' => 0,
            'commission_rate_bp' => 0,
            'commission_toman' => 0,
            'instructor_amount_toman' => 0,
            'gateway_authority' => null,
            'payment_source' => PaymentSource::Bundle,
            'paid_at' => now(),
        ];

        $enrollment = Enrollment::query()
            ->where('course_id', (int) $ref)
            ->where('student_user_id', $userId)
            ->first();

        if ($enrollment === null) {
            Enrollment::query()->create([
                'uuid' => (string) Str::uuid7(),
                'course_id' => (int) $ref,
                'student_user_id' => $userId,
                ...$values,
            ]);

            return;
        }

        if ($enrollment->status !== EnrollmentStatus::Paid) {
            $enrollment->forceFill($values)->save();
        }
    }

    private function component(Course $course): BundleComponent
    {
        return new BundleComponent(
            kind: $this->kind(),
            ref: (string) $course->id,
            title: $course->title,
            listPrice: $course->price(),
            ownerUserId: $course->instructor_user_id,
            commissionFlow: 'course',
            url: Route::has('courses.show') ? route('courses.show', $course->slug) : null,
        );
    }
}
