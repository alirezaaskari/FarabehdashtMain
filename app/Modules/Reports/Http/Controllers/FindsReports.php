<?php

declare(strict_types=1);

namespace App\Modules\Reports\Http\Controllers;

use App\Models\User;
use App\Modules\Reports\Domain\Report;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * گزارش فقط برای مالکش وجود دارد.
 */
trait FindsReports
{
    private function report(Request $request, string $uuid): Report
    {
        $report = Report::query()
            ->where('uuid', $uuid)
            ->where('user_id', $this->user($request)->getKey())
            ->first();

        // گزارش کاربر دیگر «۴۰۴» است نه «۴۰۳»: پاسخ متفاوت یعنی اعلام اینکه
        // این شناسه وجود دارد.
        return $report ?? throw new NotFoundHttpException('این گزارش پیدا نشد.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        assert($user instanceof User);

        return $user;
    }
}
