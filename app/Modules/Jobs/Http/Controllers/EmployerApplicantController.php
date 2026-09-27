<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Models\User;
use App\Modules\Jobs\Actions\PostApplicationMessage;
use App\Modules\Jobs\Actions\ReviewApplication;
use App\Modules\Jobs\Domain\Enums\ApplicationStatus;
use App\Modules\Jobs\Domain\JobApplication;
use App\Modules\Jobs\Domain\JobPosting;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * صندوق کارفرما (۲۰-۲): درخواست‌های هر آگهی، تصمیم، گفت‌وگو و دیدن شماره یا
 * رزومه که هر بار در دفتر دسترسی ثبت می‌شود. کارفرما فقط درخواست‌های آگهی
 * خودش را می‌بیند، نه درخواست‌های دیگر همان کارجو.
 */
final readonly class EmployerApplicantController
{
    public function __construct(
        private ReviewApplication $review,
        private Repository $config,
    ) {}

    public function index(Request $request, string $uuid): View
    {
        $posting = JobPosting::query()->where('uuid', $uuid)->with('company')->first();

        if ($posting === null || $posting->company->user_id !== $this->user($request)->getKey()) {
            throw new NotFoundHttpException('این آگهی پیدا نشد.');
        }

        $applications = $posting->applications()->with('user')->latest('id')->get();

        return view('jobs::employer.applicants', [
            'posting' => $posting,
            'applications' => $applications,
            'counts' => $applications->countBy(static fn (JobApplication $application): string => $application->status->value)->all(),
        ]);
    }

    public function show(Request $request, string $uuid): View
    {
        $user = $this->user($request);
        $application = $this->application($request, $uuid);

        try {
            $this->review->open($user, $application);
        } catch (RuntimeException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        $application->load('messages', 'user');

        return view('jobs::employer.applicant', [
            'application' => $application,
            'posting' => $application->posting,
            'choices' => ApplicationStatus::employerChoices(),
            'contact' => session('contact_shown') === $application->uuid && $application->share_contact ? $application->user : null,
        ]);
    }

    public function status(Request $request, string $uuid): RedirectResponse
    {
        $application = $this->application($request, $uuid);
        $validated = $request->validate(['status' => ['required', Rule::enum(ApplicationStatus::class)]]);

        try {
            $this->review->decide($this->user($request), $application, ApplicationStatus::from((string) $validated['status']));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['application' => $exception->getMessage()]);
        }

        return to_route('jobs.employer.applicants.show', $uuid)->with('status', 'وضعیت «'.$application->status->label().'» شد و کارجو خبر گرفت.');
    }

    public function contact(Request $request, string $uuid): RedirectResponse
    {
        $application = $this->application($request, $uuid);

        try {
            $this->review->contact($this->user($request), $application);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['application' => $exception->getMessage()]);
        }

        return to_route('jobs.employer.applicants.show', $uuid)->with('contact_shown', $uuid);
    }

    public function resume(Request $request, string $uuid, Factory $storage): StreamedResponse
    {
        $application = $this->application($request, $uuid);

        try {
            $path = $this->review->resume($this->user($request), $application);
        } catch (RuntimeException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        $disk = $storage->disk('local');

        if (! $disk->exists($path)) {
            throw new NotFoundHttpException('فایل روی سرور پیدا نشد.');
        }

        return $disk->download($path, $application->resume_name ?? 'resume.pdf');
    }

    public function message(Request $request, string $uuid, PostApplicationMessage $post): RedirectResponse
    {
        $application = $this->application($request, $uuid);
        $body = (string) $request->validate(['body' => ['required', 'string', 'max:'.(int) $this->config->get('jobs.applications.message_max', 2000)]])['body'];

        try {
            $post->handle($application, (int) $this->user($request)->getKey(), $body);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['body' => $exception->getMessage()]);
        }

        return to_route('jobs.employer.applicants.show', $uuid)->with('status', 'پیام فرستاده شد.');
    }

    private function application(Request $request, string $uuid): JobApplication
    {
        $application = JobApplication::query()->where('uuid', $uuid)->with('posting.company')->first();

        if ($application === null || $application->employerId() !== $this->user($request)->getKey()) {
            throw new NotFoundHttpException('این درخواست پیدا نشد.');
        }

        return $application;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
