<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Contracts\CommissionCalculator;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ثبت‌نام یک دانشجو در یک دوره — قیمت و کمیسیون همین‌جا Snapshot می‌شوند،
 * دقیقاً مثل `PlaceOrder` ماژول تجارت. جریان کمیسیون «course» است، همان
 * نرخ ۲۰٪ فروشگاه (ADR-0003) از راه همان `CommissionCalculator` مشترک.
 */
final readonly class EnrollInCourse
{
    private const string FLOW = 'course';

    public function __construct(private CommissionCalculator $commission) {}

    public function handle(int $studentUserId, Course $course): Enrollment
    {
        if (! $course->status->enrollable()) {
            throw new InvalidArgumentException('این دوره در حال حاضر قابل ثبت‌نام نیست.');
        }

        if (Enrollment::query()->where('course_id', $course->id)->where('student_user_id', $studentUserId)->exists()) {
            throw new InvalidArgumentException('شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
        }

        $split = $this->commission->split($course->price(), self::FLOW);

        return Enrollment::query()->create([
            'uuid' => (string) Str::uuid7(),
            'course_id' => $course->id,
            'student_user_id' => $studentUserId,
            'status' => EnrollmentStatus::Pending,
            'price_toman' => $course->price_toman,
            'commission_rate_bp' => $split->rateBp,
            'commission_toman' => $split->commission->toman,
            'instructor_amount_toman' => $split->vendorAmount->toman,
        ]);
    }
}
