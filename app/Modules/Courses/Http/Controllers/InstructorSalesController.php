<?php

declare(strict_types=1);

namespace App\Modules\Courses\Http\Controllers;

use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class InstructorSalesController
{
    public function index(Request $request): View
    {
        $enrollments = Enrollment::query()
            ->whereHas('course', fn ($query) => $query->where('instructor_user_id', $request->user()->id))
            ->where('status', EnrollmentStatus::Paid->value)
            ->with(['course', 'student'])
            ->latest('id')
            ->limit(100)
            ->get();

        return view('courses::instructor.sales', ['enrollments' => $enrollments]);
    }
}
