<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Actions;

use App\Modules\Bundles\Domain\Bundle;
use App\Modules\Bundles\Domain\Enums\BundleStatus;
use App\Modules\Bundles\Events\BundleStatusChanged;
use App\Modules\Bundles\Services\ComponentCatalog;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

final readonly class ChangeBundleStatus
{
    public function __construct(
        private ComponentCatalog $catalog,
        private Dispatcher $events,
    ) {}

    public function publish(Bundle $bundle, int $actorId): Bundle
    {
        if ($this->catalog->available($bundle) === null) {
            throw new InvalidArgumentException('یکی از اجزای بسته دیگر فروختنی نیست؛ بسته را ویرایش کنید.');
        }

        return $this->move($bundle, BundleStatus::Published, $actorId);
    }

    public function retire(Bundle $bundle, int $actorId): Bundle
    {
        return $this->move($bundle, BundleStatus::Retired, $actorId);
    }

    private function move(Bundle $bundle, BundleStatus $to, int $actorId): Bundle
    {
        $before = $bundle->status;

        if ($before === $to) {
            return $bundle;
        }

        $bundle->forceFill([
            'status' => $to,
            'published_at' => $to === BundleStatus::Published ? ($bundle->published_at ?? now()) : $bundle->published_at,
        ])->save();

        $this->events->dispatch(new BundleStatusChanged($bundle, $before, $actorId));

        return $bundle;
    }
}
