<?php

declare(strict_types=1);

namespace App\Modules\Projects\Http\Controllers;

use App\Models\User;
use App\Modules\Projects\Services\MonitoringCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * تقویم الزامات پایش.
 */
final readonly class MonitoringCalendarController
{
    public function __construct(private MonitoringCalendar $calendar) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        assert($user instanceof User);

        return view('projects::calendar', [
            'entries' => $this->calendar->for((int) $user->getKey()),
        ]);
    }
}
