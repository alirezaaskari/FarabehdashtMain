<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Models\User;
use App\Modules\Monetization\Domain\Team;
use App\Modules\Monetization\Domain\TeamFile;
use App\Modules\Monetization\Events\TeamFileChanged;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * کتابخانه تیم (DEC-62): فقط فایلی که خود عضو بارگذاری می‌کند، روی دیسک
 * خصوصی. فایل خریداری‌شده از فروشگاه این‌جا راهی ندارد؛ مجوزش مال خریدار است.
 * بارگذار یا صاحب تیم فایل را برمی‌دارد.
 */
final readonly class ManageTeamFiles
{
    public function __construct(
        private Filesystem $storage,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function add(User $user, Team $team, UploadedFile $file, string $title): TeamFile
    {
        if (! $team->isCurrent()) {
            throw new RuntimeException('اشتراک تیم تمام شده است؛ فایل تازه گذاشته نمی‌شود.');
        }

        $max = (int) $this->config->get('monetization.team.files_per_team', 200);

        if (TeamFile::query()->where('team_id', $team->id)->count() >= $max) {
            throw new RuntimeException('کتابخانه تیم پر است؛ فایل‌های قدیمی را بردارید.');
        }

        $uuid = (string) Str::uuid7();
        $path = $this->storage->disk('local')->putFileAs(
            'teams/'.$team->uuid,
            $file,
            $uuid.'.'.strtolower($file->getClientOriginalExtension() ?: 'bin'),
        );

        if ($path === false) {
            throw new RuntimeException('فایل ذخیره نشد؛ دوباره تلاش کنید.');
        }

        $teamFile = TeamFile::query()->create([
            'uuid' => $uuid,
            'team_id' => $team->id,
            'uploader_id' => $user->getKey(),
            'title' => trim($title),
            'original_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'path' => $path,
            'mime' => Str::limit((string) $file->getMimeType(), 120, ''),
            'size_bytes' => (int) $file->getSize(),
        ]);

        $this->events->dispatch(new TeamFileChanged($teamFile, TeamFileChanged::UPLOADED, (int) $user->getKey()));

        return $teamFile;
    }

    public function remove(User $user, TeamFile $file): void
    {
        if ($file->uploader_id !== $user->getKey() && $file->team->owner_id !== $user->getKey()) {
            throw new RuntimeException('فقط بارگذار یا صاحب تیم فایل را برمی‌دارد.');
        }

        $this->storage->disk('local')->delete($file->path);
        $file->delete();

        $this->events->dispatch(new TeamFileChanged($file, TeamFileChanged::DELETED, (int) $user->getKey()));
    }
}
