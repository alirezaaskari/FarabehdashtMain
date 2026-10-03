<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Models\User;
use App\Modules\Marketplace\Admin\PendingMarketItems;
use App\Modules\Marketplace\Domain\MarketProject;

/**
 * چه کسی چه چیزی از پروژه را می‌بیند.
 *
 * صفحه پروژه منتشرشده عمومی برای همه باز است؛ پروژه خصوصی (DEC-90) فقط برای
 * کارفرما، مدیر و مجری‌های دعوت‌شده. پیوست خصوصی فقط کارفرما و مدیر (و از
 * ۲۱-۴ مجری قرارداد).
 */
final readonly class ProjectAccess
{
    public function canView(?User $user, MarketProject $project): bool
    {
        if ($this->isOwnerOrAdmin($user, $project)) {
            return true;
        }

        if (! $project->status->isPublished()) {
            return false;
        }

        return ! $project->is_private || ($user !== null && $project->isInvited((int) $user->getKey()));
    }

    public function canDownloadFiles(?User $user, MarketProject $project): bool
    {
        return $this->isOwnerOrAdmin($user, $project);
    }

    private function isOwnerOrAdmin(?User $user, MarketProject $project): bool
    {
        return $user !== null && ($user->getKey() === $project->client_user_id || $user->can(PendingMarketItems::ABILITY));
    }
}
