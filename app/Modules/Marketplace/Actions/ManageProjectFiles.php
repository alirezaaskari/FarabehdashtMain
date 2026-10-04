<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Models\User;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Domain\ProjectFile;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * پیوست خصوصی پروژه، مثل نقشه سالن: فقط روی دیسک local، نه پوشه عمومی.
 */
final readonly class ManageProjectFiles
{
    public function __construct(
        private Factory $storage,
        private Repository $config,
    ) {}

    /** @param  list<UploadedFile>  $uploads */
    public function addAll(MarketProject $project, array $uploads): void
    {
        $max = (int) $this->config->get('marketplace.projects.files_max', 5);

        if ($project->files()->count() + count($uploads) > $max) {
            throw new RuntimeException('حداکثر '.$max.' پیوست برای هر پروژه؛ یکی را بردارید.');
        }

        foreach ($uploads as $upload) {
            $uuid = (string) Str::uuid7();
            $path = $this->storage->disk('local')->putFileAs(
                (string) $this->config->get('marketplace.projects.files_directory', 'marketplace/projects'),
                $upload,
                $uuid.'.'.strtolower($upload->getClientOriginalExtension() ?: 'bin'),
            );

            if ($path === false) {
                throw new RuntimeException('فایل ذخیره نشد؛ دوباره تلاش کنید.');
            }

            ProjectFile::query()->create([
                'uuid' => $uuid,
                'project_id' => $project->id,
                'path' => $path,
                'original_name' => Str::limit($upload->getClientOriginalName(), 180, ''),
                'size_bytes' => (int) $upload->getSize(),
            ]);
        }
    }

    public function remove(User $user, ProjectFile $file): void
    {
        $project = $file->project;

        if ($project->client_user_id !== $user->getKey()) {
            throw new RuntimeException('این پیوست مال شما نیست.');
        }

        if (! $project->status->isEditable()) {
            throw new RuntimeException('پیوست پروژه منتشرشده برداشته نمی‌شود.');
        }

        $this->storage->disk('local')->delete($file->path);
        $file->delete();
    }
}
