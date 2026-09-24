<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Contracts\WalletStatementReader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * کیف پول — فقط خواندنی.
 *
 * شارژ فقط توسط مدیر است (قاعده محصولی)؛ این صفحه هیچ فرم و دکمه مالی ندارد،
 * پس در حالت «مشاهده به‌عنوان کاربر» هم امن است.
 */
final readonly class WalletController
{
    public function __invoke(Request $request): View
    {
        // بدون دفتر کل، کیف پولی در کار نیست و صفحه هم نیست.
        abort_unless(app()->bound(WalletStatementReader::class), 404);

        $reader = app(WalletStatementReader::class);
        $userId = (int) $request->user()?->getKey();

        return view('workspace::wallet', [
            'balance' => $reader->balanceOf($userId),
            'statement' => $reader->statement(
                $userId,
                max(1, (int) $request->query('page', '1')),
                (int) config('workspace.wallet.per_page', 20),
            ),
        ]);
    }
}
