<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Widgets;

use App\Contracts\WalletStatementReader;
use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;

/**
 * موجودی کیف پول روی نمای شخصی.
 *
 * فقط وقتی ثبت می‌شود که دفتر کل روشن باشد و قرارداد خواندن کیف پول بسته
 * شده باشد؛ در غیر این صورت کارتی نیست، نه کارتی با «۰ تومان» گمراه‌کننده.
 */
final readonly class WalletWidget implements WorkspaceWidgetSource
{
    public function __construct(private WalletStatementReader $wallet) {}

    public function widgets(User $user, WorkspaceView $view): array
    {
        if (! $view->isPersonal()) {
            return [];
        }

        return [new WorkspaceWidget(
            key: 'wallet',
            title: 'کیف پول',
            order: 20,
            stats: [new WidgetStat('موجودی', $this->wallet->balanceOf((int) $user->getKey())->format())],
            actionUrl: route('workspace.wallet'),
            actionLabel: 'گردش حساب',
        )];
    }
}
