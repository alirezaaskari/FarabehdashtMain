<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\UserNotifiableEvent;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Support\Notifications\UserNotice;

/**
 * پروژه عمومی تازه منتشر شد؛ مشاوران و آزمایشگاه‌هایی که همین نوع کار یا
 * همین شهر را در صفحه‌شان دارند اعلان می‌گیرند. پروژه خصوصی اعلان عمومی ندارد.
 */
final readonly class ProjectAnnounced implements UserNotifiableEvent
{
    /** @param  list<int>  $recipientIds */
    public function __construct(
        public MarketProject $project,
        public array $recipientIds,
    ) {}

    public function userNotices(): array
    {
        return array_map(fn (int $id): UserNotice => new UserNotice(
            recipientId: $id,
            kind: 'marketplace.project_matched',
            title: 'پروژه تازه در بازار: «'.$this->project->title.'»',
            routeName: 'market.show',
            routeParameters: ['project' => $this->project->id],
        ), $this->recipientIds);
    }
}
