<?php

declare(strict_types=1);

namespace App\Modules\Tools\Actions;

use App\Models\User;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Events\CalculationSaved;
use Farabehdasht\CalcEngine\Calculation;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

/**
 * ثبت تغییرناپذیر یک محاسبه.
 *
 * شناسه و نسخه فرمول از خود نتیجه گرفته می‌شوند، نه از تعریف ابزار: اگر بین
 * اجرا و ذخیره مدیر نسخه را عوض کرده باشد، آنچه ذخیره می‌شود باید همان باشد
 * که واقعاً اجرا شد.
 */
final readonly class SaveCalculation
{
    public function __construct(private Dispatcher $events) {}

    public function handle(
        User $user,
        ResolvedTool $tool,
        Calculation $calculation,
        ?string $label = null,
    ): SavedCalculation {
        $stored = $calculation->toArray();

        /** @var array<string, mixed> $inputs */
        $inputs = $stored['inputs'];

        /** @var array<string, mixed> $outputs */
        $outputs = $stored['outputs'];

        $saved = SavedCalculation::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $user->getKey(),
            'tool_slug' => $tool->slug(),
            'formula_id' => $calculation->formulaId,
            'formula_version' => $calculation->formulaVersion,
            'label' => $label,
            'inputs' => $inputs,
            'outputs' => $outputs,
            'notes' => $calculation->notes,
        ]);

        $this->events->dispatch(new CalculationSaved($saved));

        return $saved;
    }
}
