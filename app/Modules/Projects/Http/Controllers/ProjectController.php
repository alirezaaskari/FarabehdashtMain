<?php

declare(strict_types=1);

namespace App\Modules\Projects\Http\Controllers;

use App\Contracts\ToolDirectory;
use App\Models\User;
use App\Modules\Projects\Actions\AcknowledgeEquipmentWarning;
use App\Modules\Projects\Actions\CreateProject;
use App\Modules\Projects\Actions\RecordReading;
use App\Modules\Projects\Domain\Enums\Industry;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Domain\IndustryTemplate;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Domain\ProjectRound;
use App\Modules\Projects\Domain\ProjectStation;
use App\Modules\Projects\Services\IndustryTemplates;
use App\Modules\Projects\Services\ReportReadiness;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ProjectController
{
    public function __construct(
        private IndustryTemplates $templates,
        private ReportReadiness $readiness,
    ) {}

    public function index(Request $request): View
    {
        $projects = Project::query()
            ->forUser((int) $this->user($request)->getKey())
            ->withCount(['stations', 'rounds', 'readings'])
            ->latest('id')
            ->get();

        return view('projects::index', [
            'projects' => $projects,
            'templates' => $this->templates->all(),
            // جمع‌ها در کنترلر ساخته می‌شوند، نه در قالب: قالب فقط چاپ می‌کند.
            'totals' => [
                'projects' => $projects->count(),
                'stations' => (int) $projects->sum('stations_count'),
                'rounds' => (int) $projects->sum('rounds_count'),
                'readings' => (int) $projects->sum('readings_count'),
            ],
        ]);
    }

    public function store(Request $request, CreateProject $create): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string'],
        ]);

        $industry = isset($data['industry']) && $data['industry'] !== ''
            ? Industry::tryFrom((string) $data['industry'])
            : null;

        $project = $create->handle(
            $this->user($request),
            (string) $data['title'],
            $industry,
            $data['client_name'] ?? null,
        );

        return redirect()->route('projects.show', $project->uuid);
    }

    public function show(Request $request, string $uuid): View
    {
        $project = $this->find($request, $uuid);
        $template = $project->industry === null ? null : $this->templates->for($project->industry);

        return view('projects::show', [
            'project' => $project,
            'stations' => $project->stations()->get(),
            'rounds' => $project->rounds()->get(),
            'readings' => $project->readings()->get()->keyBy(
                static fn ($reading): string => $reading->project_round_id.':'.$reading->project_station_id,
            ),
            'equipment' => Equipment::query()->forUser((int) $project->user_id)->orderBy('name')->get(),
            'warnings' => $this->readiness->warnings($project),
            'needsAcknowledgement' => $this->readiness->requiresAcknowledgement($project),
            'template' => $template,
            'templateTools' => $this->templateTools($template),
        ]);
    }

    /**
     * ابزارهای پیشنهادی قالب، با عنوان خوانا.
     *
     * عنوان از قرارداد `ToolDirectory` می‌آید، نه از مدل ماژول ابزارها. اگر
     * آن ماژول خاموش باشد، قرارداد بسته نشده و فهرست خالی برمی‌گردد — صفحه
     * پروژه بدون بخش ابزار نمایش داده می‌شود، نه اینکه بشکند.
     *
     * @return list<array{slug: string, title: string}>
     */
    private function templateTools(?IndustryTemplate $template): array
    {
        if ($template === null || $template->tools === [] || ! app()->bound(ToolDirectory::class)) {
            return [];
        }

        $directory = app(ToolDirectory::class);

        $tools = [];

        foreach ($template->tools as $slug) {
            $title = $directory->titleFor($slug);

            if ($title !== null) {
                $tools[] = ['slug' => $slug, 'title' => $title];
            }
        }

        return $tools;
    }

    public function storeReading(Request $request, string $uuid, RecordReading $record): RedirectResponse
    {
        $project = $this->find($request, $uuid);

        $data = $request->validate([
            'round_id' => ['required', 'integer'],
            'station_id' => ['required', 'integer'],
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string', 'max:32'],
            'equipment_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $round = ProjectRound::query()->findOrFail($data['round_id']);
        $station = ProjectStation::query()->findOrFail($data['station_id']);

        try {
            $record->manual(
                project: $project,
                round: $round,
                station: $station,
                value: (float) $data['value'],
                unit: (string) $data['unit'],
                equipmentId: $data['equipment_id'] ?? null,
                notes: $data['notes'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['value' => $exception->getMessage()]);
        }

        return redirect()
            ->route('projects.show', $project->uuid)
            ->with('status', 'قرائت ثبت شد.');
    }

    public function acknowledge(
        Request $request,
        string $uuid,
        AcknowledgeEquipmentWarning $acknowledge,
    ): RedirectResponse {
        $project = $this->find($request, $uuid);

        try {
            $acknowledge->handle($project, (int) $this->user($request)->getKey());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['acknowledge' => $exception->getMessage()]);
        }

        return redirect()
            ->route('projects.show', $project->uuid)
            ->with('status', 'هشدار کالیبراسیون تأیید شد و در دفتر رویداد ثبت شد.');
    }

    private function find(Request $request, string $uuid): Project
    {
        $project = Project::query()
            ->where('uuid', $uuid)
            ->where('user_id', $this->user($request)->getKey())
            ->first();

        // پروژه کاربر دیگر «۴۰۴» است نه «۴۰۳»: پاسخ متفاوت یعنی اعلام اینکه
        // این شناسه وجود دارد.
        return $project ?? throw new NotFoundHttpException('این پروژه پیدا نشد.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        assert($user instanceof User);

        return $user;
    }
}
