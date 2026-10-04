<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Models\User;
use App\Modules\Marketplace\Admin\PendingMarketItems;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketProject;

/**
 * چه کسی چه چیزی از پروژه را می‌بیند.
 *
 * صفحه پروژه منتشرشده عمومی برای همه باز است؛ پروژه خصوصی (DEC-90) فقط برای
 * کارفرما، مدیر و مجری‌های دعوت‌شده. پیوست خصوصی فقط کارفرما و مدیر (و مجری
 * قراردادی که مرحله اولش پرداخت شده).
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
        return $this->isOwnerOrAdmin($user, $project) || ($user !== null && MarketContract::query()
            ->where('project_id', $project->id)
            ->where('provider_user_id', $user->getKey())
            ->whereIn('status', [ContractStatus::Active, ContractStatus::Completed])
            ->exists());
    }

    private function isOwnerOrAdmin(?User $user, MarketProject $project): bool
    {
        return $user !== null && ($user->getKey() === $project->client_user_id || $user->can(PendingMarketItems::ABILITY));
    }
}
