<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Http\Controllers;

use App\Models\User;
use App\Modules\Marketplace\Actions\SubmitBid;
use App\Modules\Marketplace\Domain\BidDraft;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketInvite;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Services\ContractTerms;
use App\Modules\Marketplace\Services\MarketCatalog;
use App\Modules\Marketplace\Services\StrikeBook;
use App\Support\Money;
use App\Support\PersianNumber;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * پیشنهاد مجری: فرم پیشنهاد روی یک پروژه، فهرست پیشنهادها و دعوت‌های من، و پس‌گرفتن.
 */
final readonly class BidController
{
    public function __construct(
        private MarketCatalog $catalog,
        private StrikeBook $strikes,
        private Repository $config,
        private ContractTerms $terms,
    ) {}

    public function mine(Request $request): View
    {
        $user = $this->user($request);

        return view('marketplace::bids.mine', [
            'bids' => MarketBid::query()->where('provider_user_id', $user->getKey())->with(['project', 'contract'])->latest('updated_at')->get(),
            'invites' => MarketInvite::query()
                ->where('provider_user_id', $user->getKey())
                ->whereDoesntHave('project.bids', static fn ($query) => $query->where('provider_user_id', $user->getKey()))
                ->with('project')
                ->latest('id')
                ->get()
                ->filter(static fn (MarketInvite $invite): bool => $invite->project->acceptsBids()),
            'catalog' => $this->catalog,
            'blocked' => $this->strikes->isBlocked((int) $user->getKey()),
        ]);
    }

    public function create(Request $request, int $project, SubmitBid $submit): View|RedirectResponse
    {
        $user = $this->user($request);
        $record = MarketProject::query()->findOrFail($project);

        try {
            $submit->ensureCanBid($user, $record);
        } catch (RuntimeException $exception) {
            return to_route('market.show', $record->id)->withErrors(['bid' => $exception->getMessage()]);
        }

        return view('marketplace::bids.form', [
            'project' => $record,
            'bid' => MarketBid::query()->where('project_id', $record->id)->where('provider_user_id', $user->getKey())->first(),
            'catalog' => $this->catalog,
            'limits' => (array) $this->config->get('marketplace.bids', []),
            'commission' => PersianNumber::percent($this->terms->rateBp() / 100, $this->terms->rateBp() % 100 === 0 ? 0 : 1),
            'milestoneMin' => Money::toman((int) $this->config->get('marketplace.bids.milestone_min_toman', 500_000))->format(),
        ]);
    }

    public function store(Request $request, int $project, SubmitBid $submit): RedirectResponse
    {
        $record = MarketProject::query()->findOrFail($project);
        $draft = $this->draft($request);

        try {
            $bid = $submit->submit($this->user($request), $record, $draft);
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['bid' => $exception->getMessage()]);
        }

        return to_route('market.bids.show', $bid->uuid)->with('status', 'پیشنهاد برای کارفرما فرستاده شد. تا انتخاب کارفرما می‌توانید ویرایش یا پس‌اش بگیرید.');
    }

    public function withdraw(Request $request, string $uuid, SubmitBid $submit): RedirectResponse
    {
        $bid = MarketBid::query()->where('uuid', $uuid)->with('project')->first() ?? throw new NotFoundHttpException;

        try {
            $submit->withdraw($this->user($request), $bid);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['bid' => $exception->getMessage()]);
        }

        return to_route('market.bids.mine')->with('status', 'پیشنهاد پس گرفته شد.');
    }

    private function draft(Request $request): BidDraft
    {
        $limits = (array) $this->config->get('marketplace.bids', []);
        $max = (int) ($limits['milestones_max'] ?? 5);
        $rows = [];

        // ردیف‌های خالی فرم نادیده گرفته می‌شوند؛ مبلغ با جداکننده فارسی یا لاتین نوشته می‌شود.
        foreach (array_slice((array) $request->input('milestones', []), 0, $max) as $row) {
            $row = is_array($row) ? $row : [];
            $title = trim((string) ($row['title'] ?? ''));
            $amount = trim((string) ($row['amount'] ?? ''));

            if ($title === '' && $amount === '') {
                continue;
            }

            try {
                $amount = Money::fromInput($amount)->toman;
            } catch (InvalidArgumentException) {
                // همان متن می‌ماند تا قاعده integer پیام خطا بدهد.
            }

            $rows[] = ['title' => $title, 'amount' => $amount, 'days' => $row['days'] ?? null];
        }

        $request->merge(['rows' => $rows]);

        $validated = $request->validate([
            'cover' => ['required', 'string', 'min:'.($limits['cover_min'] ?? 40), 'max:'.($limits['cover_max'] ?? 3000)],
            'rows' => ['required', 'array', 'min:1', 'max:'.$max],
            'rows.*.title' => ['required', 'string', 'min:3', 'max:'.($limits['milestone_title_max'] ?? 120)],
            'rows.*.amount' => ['required', 'integer', 'min:1', 'max:10000000000'],
            'rows.*.days' => ['required', 'integer', 'min:1', 'max:'.($limits['milestone_days_max'] ?? 180)],
        ], [
            'rows.required' => 'دست‌کم یک مرحله با عنوان، مبلغ و روز بنویسید.',
            'rows.*.title.required' => 'هر مرحله عنوان می‌خواهد.',
            'rows.*.amount.required' => 'هر مرحله مبلغ می‌خواهد.',
            'rows.*.amount.integer' => 'مبلغ مرحله را فقط با رقم بنویسید.',
            'rows.*.days.required' => 'هر مرحله تعداد روز می‌خواهد.',
        ]);

        return new BidDraft(
            cover: trim((string) $validated['cover']),
            milestones: array_map(static fn (array $row): array => [
                'title' => trim((string) $row['title']),
                'amount_toman' => (int) $row['amount'],
                'days' => (int) $row['days'],
            ], array_values($validated['rows'])),
        );
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
