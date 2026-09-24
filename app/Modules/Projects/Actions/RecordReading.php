<?php

declare(strict_types=1);

namespace App\Modules\Projects\Actions;

use App\Contracts\CalculationReader;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Domain\ProjectReading;
use App\Modules\Projects\Domain\ProjectRound;
use App\Modules\Projects\Domain\ProjectStation;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * ثبت قرائت یک ایستگاه در یک دور.
 *
 * دو راه ورود دارد و هر دو به یک ردیف می‌رسند:
 *
 *   دستی    مقدار و واحد را کاربر می‌دهد
 *   محاسبه  شناسه یک محاسبه ذخیره‌شده داده می‌شود و مقدار از آن خوانده می‌شود
 *
 * راه دوم از قرارداد `CalculationReader` می‌گذرد و نه از مدل ماژول ابزارها
 * (قاعده ۱). اگر آن ماژول خاموش باشد، قرارداد بسته نیست و فقط راه دستی
 * کار می‌کند — بدون خطا در بارگذاری.
 *
 * مقدار در هر دو حالت **کپی** می‌شود و ارجاع نمی‌ماند: پروژه‌ای که عددش به
 * جدول ماژول دیگری وابسته باشد، با خاموش‌شدن آن ماژول بی‌معنا می‌شود.
 */
final readonly class RecordReading
{
    public function __construct(private Container $container) {}

    public function manual(
        Project $project,
        ProjectRound $round,
        ProjectStation $station,
        float $value,
        string $unit,
        ?int $equipmentId = null,
        ?string $notes = null,
    ): ProjectReading {
        return $this->store($project, $round, $station, [
            'value' => $value,
            'unit' => $unit,
            'equipment_id' => $equipmentId,
            'notes' => $notes,
        ]);
    }

    /**
     * @throws RuntimeException اگر ماژول ابزارها در دسترس نباشد یا محاسبه پیدا نشود
     */
    public function fromCalculation(
        Project $project,
        ProjectRound $round,
        ProjectStation $station,
        string $calculationUuid,
        ?int $equipmentId = null,
        ?string $notes = null,
    ): ProjectReading {
        if (! $this->container->bound(CalculationReader::class)) {
            throw new RuntimeException('ماژول ابزارها در دسترس نیست؛ قرائت را دستی وارد کنید.');
        }

        $reader = $this->container->make(CalculationReader::class);
        $calculation = $reader->findForUser($calculationUuid, (int) $project->user_id);

        if ($calculation === null) {
            throw new RuntimeException('این محاسبه پیدا نشد یا متعلق به شما نیست.');
        }

        return $this->store($project, $round, $station, [
            'value' => $calculation->headlineValue,
            'unit' => $calculation->headlineUnit,
            'calculation_uuid' => $calculation->uuid,
            'tool_slug' => $calculation->toolSlug,
            'equipment_id' => $equipmentId,
            'notes' => $notes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function store(
        Project $project,
        ProjectRound $round,
        ProjectStation $station,
        array $attributes,
    ): ProjectReading {
        $this->guard($project, $round, $station, $attributes['equipment_id'] ?? null);

        // یک قرائت برای هر ایستگاه در هر دور. ثبت دوباره، همان ردیف را
        // به‌روز می‌کند تا داده مبهم نشود.
        return ProjectReading::query()->updateOrCreate(
            [
                'project_round_id' => $round->getKey(),
                'project_station_id' => $station->getKey(),
            ],
            [...$attributes, 'project_id' => $project->getKey()],
        );
    }

    private function guard(Project $project, ProjectRound $round, ProjectStation $station, mixed $equipmentId): void
    {
        if (! $project->editable()) {
            throw new RuntimeException('این پروژه بایگانی شده و قرائت تازه نمی‌پذیرد.');
        }

        if ($round->project_id !== $project->getKey() || $station->project_id !== $project->getKey()) {
            throw new RuntimeException('دور یا ایستگاه انتخاب‌شده به این پروژه تعلق ندارد.');
        }

        // مشخصات تجهیز در گزارش PDF چاپ می‌شود؛ تجهیز کاربر دیگر یعنی نشت
        // نام و شماره سریال او.
        if ($equipmentId !== null && ! Equipment::query()->forUser((int) $project->user_id)->whereKey($equipmentId)->exists()) {
            throw new RuntimeException('این تجهیز در دفترچه تجهیزات شما نیست.');
        }
    }
}
