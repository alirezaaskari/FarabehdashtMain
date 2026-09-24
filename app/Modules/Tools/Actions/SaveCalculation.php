<?php

declare(strict_types=1);

namespace App\Modules\Tools\Actions;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Events\CalculationSaved;
use App\Support\Entitlement\EntitlementDenied;
use App\Support\Entitlement\Feature;
use Farabehdasht\CalcEngine\Calculation;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

/**
 * ثبت تغییرناپذیر یک محاسبه.
 *
 * شناسه و نسخه فرمول از خود نتیجه گرفته می‌شوند، نه از تعریف ابزار: اگر بین
 * اجرا و ذخیره مدیر نسخه را عوض کرده باشد، آنچه ذخیره می‌شود باید همان باشد
 * که واقعاً اجرا شد.
 *
 * سقف پلن رایگان همین‌جا اجرا می‌شود و نه در کنترلر (قاعده ۴): هر مسیری که
 * فردا این اکشن را صدا بزند، خودبه‌خود محافظت‌شده است. خود محاسبه هیچ‌وقت
 * سقف نمی‌خورد — فقط ماندگارکردنش.
 */
final readonly class SaveCalculation
{
    public function __construct(
        private Dispatcher $events,
        private EntitlementGate $gate,
    ) {}

    public function handle(
        User $user,
        ResolvedTool $tool,
        Calculation $calculation,
        ?string $label = null,
    ): SavedCalculation {
        $decision = $this->gate->decide($user, Feature::SaveCalculation);

        if ($decision->denied()) {
            throw new EntitlementDenied($decision);
        }

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
