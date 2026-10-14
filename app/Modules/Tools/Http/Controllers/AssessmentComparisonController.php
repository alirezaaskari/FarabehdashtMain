<?php

declare(strict_types=1);

namespace App\Modules\Tools\Http\Controllers;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Services\AssessmentComparer;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\Entitlement\EntitlementDenied;
use App\Support\Entitlement\Feature;
use App\Support\Entitlement\UpgradeRedirect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * مقایسه ارزیابی‌های پوسچر ذخیره‌شده — چند ایستگاه، یا پیش و پس از اصلاح.
 *
 * فقط ارزیابی‌های خود کاربر خوانده می‌شوند؛ شناسه‌ای که مال دیگری است
 * بی‌صدا کنار می‌رود و انتخاب ناقص می‌شود، نه اینکه وجودش اعلام شود.
 */
final readonly class AssessmentComparisonController
{
    public function __construct(
        private ToolCatalog $catalog,
        private AssessmentComparer $comparer,
        private EntitlementGate $gate,
    ) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        $decision = $this->gate->decide($user, Feature::CompareAssessments);

        if ($decision->denied()) {
            return UpgradeRedirect::from(new EntitlementDenied($decision));
        }

        $uuids = array_values(array_unique(array_filter(
            (array) $request->query('c', []),
            static fn (mixed $uuid): bool => is_string($uuid) && $uuid !== '',
        )));

        $assessments = SavedCalculation::query()
            ->forUser((int) $user->getKey())
            ->whereIn('uuid', array_slice($uuids, 0, AssessmentComparer::MAX + 1))
            ->oldest('id')
            ->get()
            ->all();

        $comparison = null;
        $error = null;
        $slug = $assessments === [] ? null : $assessments[0]->tool_slug;

        try {
            if ($slug === null || ! $this->catalog->has($slug)) {
                throw new InvalidArgumentException('ارزیابی‌های انتخاب‌شده پیدا نشدند.');
            }

            $comparison = $this->comparer->compare($this->catalog->resolve($slug), $assessments);
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        }

        return view('tools::compare', [
            'comparison' => $comparison,
            'error' => $error,
        ]);
    }
}
