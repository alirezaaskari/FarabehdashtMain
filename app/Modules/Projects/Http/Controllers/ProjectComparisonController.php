<?php

declare(strict_types=1);

namespace App\Modules\Projects\Http\Controllers;

use App\Models\User;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Domain\ProjectRound;
use App\Modules\Projects\Services\ComparisonChart;
use App\Modules\Projects\Services\RoundComparer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * مقایسه دو دور اندازه‌گیری — جدول و نمودار، از یک منبع.
 */
final readonly class ProjectComparisonController
{
    public function __construct(
        private RoundComparer $comparer,
        private ComparisonChart $chart,
    ) {}

    public function __invoke(Request $request, string $uuid): View
    {
        $user = $request->user();

        assert($user instanceof User);

        $project = Project::query()
            ->where('uuid', $uuid)
            ->where('user_id', $user->getKey())
            ->first() ?? throw new NotFoundHttpException('این پروژه پیدا نشد.');

        $rounds = $project->rounds()->get();

        $before = $this->round($rounds, $request->integer('before')) ?? $rounds->first();
        $after = $this->round($rounds, $request->integer('after')) ?? $rounds->skip(1)->first();

        $comparison = null;
        $error = null;

        if ($before instanceof ProjectRound && $after instanceof ProjectRound && ! $before->is($after)) {
            try {
                $comparison = $this->comparer->compare($project, $before, $after);
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        return view('projects::compare', [
            'project' => $project,
            'rounds' => $rounds,
            'before' => $before,
            'after' => $after,
            'comparison' => $comparison,
            'chart' => $this->chart,
            'error' => $error,
        ]);
    }

    /**
     * @param  Collection<int, ProjectRound>  $rounds
     */
    private function round(Collection $rounds, int $id): ?ProjectRound
    {
        return $id === 0 ? null : $rounds->firstWhere('id', $id);
    }
}
