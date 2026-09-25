<?php

declare(strict_types=1);

namespace App\Modules\Courses\Http\Controllers;

use App\Modules\Courses\Services\LearnerCourses;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** «دوره‌های من» در میزکار: ادامه یادگیری از همان جایی که مانده بود. */
final readonly class MyCoursesController
{
    public function __construct(private LearnerCourses $courses) {}

    public function __invoke(Request $request): View
    {
        return view('courses::mine', [
            'enrollments' => $this->courses->for((int) $request->user()?->getKey()),
        ]);
    }
}
