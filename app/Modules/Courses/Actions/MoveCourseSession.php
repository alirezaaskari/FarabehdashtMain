<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * جابه‌جایی یک جلسه یک پله بالا یا پایین (بخش ۱۸-۱۱).
 *
 * ترتیب محتوا را عوض نمی‌کند، پس تأیید تازه مدیر نمی‌خواهد؛ پیشرفت دانشجو
 * به شناسه جلسه بسته است و دست نمی‌خورد. پیش از جابه‌جایی جایگاه‌ها ۱ تا n
 * بازشماری می‌شوند تا جایگاه تکراری یا جاافتاده جابه‌جایی را خراب نکند.
 */
final readonly class MoveCourseSession
{
    public function handle(Course $course, CourseSession $session, bool $up): void
    {
        if ($session->course_id !== $course->id) {
            throw new InvalidArgumentException('این جلسه از این دوره نیست.');
        }

        if ($course->status === CourseStatus::Retired) {
            throw new RuntimeException('ترتیب جلسه‌های دوره بازنشسته‌شده عوض نمی‌شود.');
        }

        DB::transaction(function () use ($course, $session, $up): void {
            $ids = $course->sessions()->orderBy('id')->lockForUpdate()->pluck('id')->all();
            $from = array_search($session->id, $ids, true);
            $to = $up ? $from - 1 : $from + 1;

            if ($from === false || ! isset($ids[$to])) {
                return;
            }

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $index => $id) {
                CourseSession::query()->whereKey($id)->update(['position' => $index + 1]);
            }
        });
    }
}
