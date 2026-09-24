<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Modules\Workspace\Actions\SubscribeToIncident;
use App\Modules\Workspace\Domain\Enums\Service;
use App\Modules\Workspace\Domain\IncidentSubscription;
use App\Modules\Workspace\Domain\ServiceIncident;
use App\Modules\Workspace\Services\StatusBoard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * صفحه عمومی وضعیت سرویس.
 */
final readonly class StatusController
{
    public function __construct(private StatusBoard $board) {}

    public function show(Request $request): View
    {
        $open = $this->board->open();
        $userId = $request->user()?->getKey();

        return view('workspace::status', [
            'services' => Service::cases(),
            'current' => $this->board->current(),
            'overall' => $this->board->overall(),
            'history' => $this->board->history(),
            'days' => $this->board->days(),
            'open' => $open,
            'upcoming' => $this->board->upcoming(),
            'resolved' => $this->board->resolved(),
            'subscribed' => $userId === null ? [] : IncidentSubscription::query()
                ->where('user_id', $userId)
                ->whereIn('incident_id', $open->modelKeys())
                ->pluck('incident_id')
                ->all(),
        ]);
    }

    public function subscribe(Request $request, ServiceIncident $incident, SubscribeToIncident $subscribe): RedirectResponse
    {
        try {
            $subscribe->handle($incident, (int) $request->user()?->getKey());
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('workspace.status')->with('status', $exception->getMessage());
        }

        return redirect()->route('workspace.status')
            ->with('status', 'وقتی این اختلال رفع شود، در اعلان‌های میزکار خبرتان می‌کنیم.');
    }
}
