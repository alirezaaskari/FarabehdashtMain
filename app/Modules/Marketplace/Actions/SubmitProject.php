<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Models\User;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Domain\ProjectDraft;
use App\Modules\Marketplace\Events\ProjectSubmitted;
use App\Modules\Marketplace\Services\StrikeBook;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * کارفرما پروژه تازه یا اصلاح پروژه برگشته را برای تأیید مدیر می‌فرستد (DEC-76).
 *
 * هر کاربر واردشده با موبایل تأییدشده پروژه تعریف می‌کند، نه فقط نقش
 * کارفرمای کاریابی. پروژه باز دیگر ویرایش نمی‌شود؛ فقط بسته می‌شود.
 */
final readonly class SubmitProject
{
    public function __construct(
        private ManageProjectFiles $files,
        private StrikeBook $strikes,
        private Repository $config,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    /** @param  list<UploadedFile>  $uploads */
    public function create(User $user, ProjectDraft $draft, array $uploads = []): MarketProject
    {
        $this->ensureCanPost($user);

        $active = MarketProject::query()
            ->where('client_user_id', $user->getKey())
            ->whereIn('status', [ProjectStatus::Pending, ProjectStatus::Rejected, ProjectStatus::Open])
            ->count();
        $max = (int) $this->config->get('marketplace.projects.active_max', 10);

        if ($active >= $max) {
            throw new RuntimeException('بیش از '.$max.' پروژه باز یا در انتظار هم‌زمان نمی‌شود داشت؛ یکی را ببندید.');
        }

        $project = $this->db->transaction(function () use ($user, $draft, $uploads): MarketProject {
            $project = MarketProject::query()->create([
                ...$draft->attributes(),
                'uuid' => (string) Str::uuid7(),
                'client_user_id' => $user->getKey(),
                'status' => ProjectStatus::Pending,
                'submitted_at' => Carbon::now(),
            ]);

            $this->files->addAll($project, $uploads);

            return $project;
        });

        $this->events->dispatch(new ProjectSubmitted($project, (int) $user->getKey()));

        return $project;
    }

    /** @param  list<UploadedFile>  $uploads */
    public function update(User $user, MarketProject $project, ProjectDraft $draft, array $uploads = []): MarketProject
    {
        $this->ensureCanPost($user);

        if ($project->client_user_id !== $user->getKey()) {
            throw new RuntimeException('این پروژه مال شما نیست.');
        }

        if (! $project->status->isEditable()) {
            throw new RuntimeException('پروژه منتشرشده ویرایش نمی‌شود تا پیشنهادهای رسیده روی متن دیگری نمانند؛ اگر لازم است، ببندید و پروژه تازه تعریف کنید.');
        }

        $this->db->transaction(function () use ($project, $draft, $uploads): void {
            $project->forceFill([
                ...$draft->attributes(),
                'status' => ProjectStatus::Pending,
                'submitted_at' => Carbon::now(),
            ])->save();

            $this->files->addAll($project, $uploads);
        });

        $this->events->dispatch(new ProjectSubmitted($project, (int) $user->getKey()));

        return $project;
    }

    public function ensureCanPost(User $user): void
    {
        if ($user->mobile_verified_at === null) {
            throw new RuntimeException('برای تعریف پروژه اول شماره موبایلتان را در «حساب من» تأیید کنید.');
        }

        if ($this->strikes->isBlocked((int) $user->getKey())) {
            throw new RuntimeException('دسترسی تعریف پروژه شما به‌خاطر تلاش برای ردوبدل راه تماس بسته شده است؛ مدیر باید دوباره باز کند.');
        }
    }
}
