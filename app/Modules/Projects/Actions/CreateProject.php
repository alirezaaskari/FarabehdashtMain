<?php

declare(strict_types=1);

namespace App\Modules\Projects\Actions;

use App\Models\User;
use App\Modules\Projects\Domain\Enums\Industry;
use App\Modules\Projects\Domain\Enums\ProjectStatus;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Services\IndustryTemplates;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

/**
 * ساخت پروژه، با یا بدون قالب صنعتی.
 *
 * قالب فقط **نقطه شروع** است: ایستگاه‌های پیشنهادی ساخته می‌شوند و کاربر
 * می‌تواند حذف و اضافه کند. هیچ ایستگاهی قفل نیست.
 *
 * دو دور اولیه هم ساخته می‌شود («دور اول» و «دور دوم»)، چون کل ارزش این
 * ماژول در مقایسه دو دور است و پروژه‌ای که یک دور دارد آن را نشان نمی‌دهد.
 */
final readonly class CreateProject
{
    public function __construct(
        private IndustryTemplates $templates,
        private DatabaseManager $db,
    ) {}

    public function handle(
        User $user,
        string $title,
        ?Industry $industry = null,
        ?string $clientName = null,
    ): Project {
        return $this->db->transaction(function () use ($user, $title, $industry, $clientName): Project {
            $project = Project::query()->create([
                'uuid' => (string) Str::uuid7(),
                'user_id' => $user->getKey(),
                'title' => $title,
                'client_name' => $clientName,
                'industry' => $industry,
                'status' => ProjectStatus::Draft,
                'started_on' => now()->toDateString(),
            ]);

            $template = $industry === null ? null : $this->templates->for($industry);
            $stations = $template === null ? [] : $template->stations;

            foreach ($stations as $index => $station) {
                $project->stations()->create(['title' => $station, 'sort_order' => $index]);
            }

            foreach (['دور اول', 'دور دوم'] as $index => $round) {
                $project->rounds()->create(['title' => $round, 'sort_order' => $index]);
            }

            return $project->fresh() ?? $project;
        });
    }
}
