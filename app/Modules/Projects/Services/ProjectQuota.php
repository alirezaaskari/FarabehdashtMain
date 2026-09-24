<?php

declare(strict_types=1);

namespace App\Modules\Projects\Services;

use App\Contracts\QuotaCounter;
use App\Models\User;
use App\Modules\Projects\Domain\Project;
use App\Support\Entitlement\Feature;

/**
 * چند پروژه این کاربر ساخته — ورودی لایه دسترسی برای سقف پلن رایگان.
 *
 * پروژه بایگانی‌شده هم شمرده می‌شود: پاک‌نشدن داده یعنی جا اشغال شده، و
 * نشمردنش راهی می‌ساخت که کاربر با بایگانی‌کردن پی‌درپی، سقف را دور بزند.
 */
final readonly class ProjectQuota implements QuotaCounter
{
    public function feature(): Feature
    {
        return Feature::CreateProject;
    }

    public function countFor(User $user): int
    {
        return Project::query()->forUser((int) $user->getKey())->count();
    }
}
