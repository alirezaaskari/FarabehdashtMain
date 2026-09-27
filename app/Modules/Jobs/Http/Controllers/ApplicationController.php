<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Http\Controllers;

use App\Models\User;
use App\Modules\Jobs\Actions\ManageOwnApplication;
use App\Modules\Jobs\Actions\PostApplicationMessage;
use App\Modules\Jobs\Actions\SubmitApplication;
use App\Modules\Jobs\Domain\JobApplication;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Domain\ResumeAccessLog;
use App\Modules\Jobs\Services\JobCatalog;
use App\Modules\Jobs\Services\JobPricing;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * درخواست‌های کارجو (۲۰-۲): فرستادن، پیگیری، گفت‌وگو، اجازه تماس و پس‌گرفتن.
 * ارسال درخواست هیچ‌وقت پول نمی‌خواهد.
 */
final readonly class ApplicationController
{
    public function __construct(
        private JobCatalog $catalog,
        private JobPricing $pricing,
        private Repository $config,
    ) {}

    public function create(Request $request, int $posting): View|RedirectResponse
    {
        $user = $this->user($request);
        $job = $this->livePosting($posting);

        $existing = JobApplication::query()->where('posting_id', $job->id)->where('user_id', $user->getKey())->first();

        if ($existing !== null) {
            return to_route('jobs.applications.show', $existing->uuid);
        }

        return view('jobs::applications.create', [
            'posting' => $job,
            'catalog' => $this->catalog,
            'canApply' => $user->can(SubmitApplication::ABILITY),
            'ownPosting' => $job->company->user_id === $user->getKey(),
            'limits' => (array) $this->config->get('jobs.applications', []),
            'perDay' => $this->pricing->applicationsPerDay(),
        ]);
    }

    public function store(Request $request, int $posting, SubmitApplication $submit): RedirectResponse
    {
        $limits = (array) $this->config->get('jobs.applications', []);

        $validated = $request->validate([
            'cover_note' => ['required', 'string', 'min:'.($limits['cover_min'] ?? 30), 'max:'.($limits['cover_max'] ?? 2000)],
            'resume' => ['required', 'file', 'mimes:pdf', 'max:'.($limits['resume_max_kb'] ?? 5120)],
            'share_contact' => ['nullable', 'boolean'],
        ], [
            'resume.mimes' => 'رزومه فقط PDF پذیرفته می‌شود.',
        ]);

        $file = $request->file('resume');

        try {
            $application = $submit->handle(
                $this->user($request),
                $this->livePosting($posting),
                (string) $validated['cover_note'],
                $file instanceof UploadedFile ? $file : throw new RuntimeException('فایل رزومه انتخاب نشده.'),
                (bool) ($validated['share_contact'] ?? false),
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['application' => $exception->getMessage()]);
        }

        return to_route('jobs.applications.show', $application->uuid)->with('status', 'درخواست فرستاده شد. هر تغییر وضعیت را با اعلان خبر می‌دهیم.');
    }

    public function index(Request $request): View
    {
        $userId = $this->user($request)->getKey();

        return view('jobs::applications.index', [
            'applications' => JobApplication::query()->where('user_id', $userId)->with('posting.company')->latest('id')->get(),
            'accesses' => ResumeAccessLog::query()->where('jobseeker_id', $userId)->with('company')->latest('id')->limit(30)->get(),
        ]);
    }

    public function show(Request $request, string $uuid): View
    {
        $application = $this->own($request, $uuid);
        $application->load('messages');

        return view('jobs::applications.show', [
            'application' => $application,
            'posting' => $application->posting,
            'catalog' => $this->catalog,
            'accesses' => ResumeAccessLog::query()->where('application_id', $application->id)->latest('id')->get(),
        ]);
    }

    public function message(Request $request, string $uuid, PostApplicationMessage $post): RedirectResponse
    {
        $application = $this->own($request, $uuid);
        $body = (string) $request->validate(['body' => ['required', 'string', 'max:'.(int) $this->config->get('jobs.applications.message_max', 2000)]])['body'];

        try {
            $post->handle($application, $application->user_id, $body);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['body' => $exception->getMessage()]);
        }

        return to_route('jobs.applications.show', $uuid)->with('status', 'پیام فرستاده شد.');
    }

    public function consent(Request $request, string $uuid, ManageOwnApplication $manage): RedirectResponse
    {
        $application = $this->own($request, $uuid);
        $share = $request->boolean('share_contact');

        try {
            $manage->shareContact($application->user_id, $application, $share);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['application' => $exception->getMessage()]);
        }

        return to_route('jobs.applications.show', $uuid)->with('status', $share
            ? 'از این پس کارفرمای همین آگهی می‌تواند شماره و ایمیل شما را ببیند.'
            : 'اجازه برداشته شد؛ کارفرما دیگر شماره و ایمیل شما را نمی‌بیند.');
    }

    public function withdraw(Request $request, string $uuid, ManageOwnApplication $manage): RedirectResponse
    {
        $application = $this->own($request, $uuid);

        try {
            $manage->withdraw($application->user_id, $application);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['application' => $exception->getMessage()]);
        }

        return to_route('jobs.applications.index')->with('status', 'درخواست پس گرفته شد و فایل رزومه‌اش پاک شد.');
    }

    private function livePosting(int $id): JobPosting
    {
        $posting = JobPosting::query()->with('company')->find($id);

        if ($posting === null || ! $posting->isLive()) {
            throw new NotFoundHttpException('این آگهی دیگر درخواست نمی‌گیرد.');
        }

        return $posting;
    }

    private function own(Request $request, string $uuid): JobApplication
    {
        $application = JobApplication::query()->where('uuid', $uuid)->with('posting.company')->first();

        if ($application === null || $application->user_id !== $this->user($request)->getKey()) {
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
