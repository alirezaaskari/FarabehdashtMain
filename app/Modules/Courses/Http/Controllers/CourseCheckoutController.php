<?php

declare(strict_types=1);

namespace App\Modules\Courses\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Modules\Courses\Actions\CompleteEnrollmentPayment;
use App\Modules\Courses\Actions\EnrollInCourse;
use App\Modules\Courses\Actions\StartCourseCheckout;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * ثبت‌نام مستقیم — بدون سبد، چون هر ثبت‌نام دقیقاً یک دوره است.
 */
final readonly class CourseCheckoutController
{
    public function __construct(
        private EnrollInCourse $enroll,
        private StartCourseCheckout $startCheckout,
        private CompleteEnrollmentPayment $completePayment,
        private PaymentGateway $gateway,
    ) {}

    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        try {
            $enrollment = $this->enroll->handle((int) $user->id, $course);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['course' => $exception->getMessage()]);
        }

        $result = $this->startCheckout->handle($enrollment, $user->mobile ?? null);

        return redirect()->away($result->redirectUrl);
    }

    public function callback(Request $request): View
    {
        $enrollment = Enrollment::query()
            ->where('gateway_authority', (string) $request->query('Authority'))
            ->with('course')
            ->firstOrFail();

        if ($enrollment->status === EnrollmentStatus::Paid) {
            return view('courses::checkout-success', ['enrollment' => $enrollment]);
        }

        if ((string) $request->query('Status') !== 'OK') {
            $enrollment->forceFill(['status' => EnrollmentStatus::Failed])->save();

            return view('courses::checkout-failed', ['enrollment' => $enrollment, 'reason' => 'پرداخت توسط شما لغو شد.']);
        }

        $verification = $this->gateway->verify((string) $enrollment->gateway_authority, $enrollment->price());

        if (! $verification->successful) {
            $enrollment->forceFill(['status' => EnrollmentStatus::Failed])->save();

            return view('courses::checkout-failed', ['enrollment' => $enrollment, 'reason' => $verification->failureReason]);
        }

        $paid = $this->completePayment->handle($enrollment, $verification->referenceId ?? '');

        return view('courses::checkout-success', ['enrollment' => $paid]);
    }
}
