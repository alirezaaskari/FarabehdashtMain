<?php

declare(strict_types=1);

namespace App\Modules\Tools\Actions;

use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Services\ToolCatalog;
use App\Modules\Tools\Services\ToolDisabled;
use App\Modules\Tools\Services\ToolInputCaster;
use Farabehdasht\CalcEngine\Calculation;
use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\Exception\InvalidInput;

/**
 * اجرای یک ابزار روی ورودی فرم.
 *
 * نسخه فرمول **صریح** پاس داده می‌شود، نه «آخرین نسخه»: اگر مدیر نسخه‌ای را
 * سنجاق کرده باشد، همان باید اجرا شود و نتیجه هم با همان نسخه ذخیره شود.
 */
final readonly class RunCalculation
{
    public function __construct(
        private ToolCatalog $catalog,
        private Engine $engine,
        private ToolInputCaster $caster,
    ) {}

    /**
     * @param  array<string, mixed>  $request
     *
     * @throws ToolDisabled
     * @throws InvalidInput
     */
    public function handle(ResolvedTool $tool, array $request): Calculation
    {
        if (! $tool->usable()) {
            throw ToolDisabled::slug($tool->slug());
        }

        return $this->engine->run(
            $tool->formula->id(),
            $this->caster->cast($tool->formula->inputs(), $request),
            $tool->version(),
        );
    }

    /**
     * @param  array<string, mixed>  $request
     *
     * @throws ToolDisabled
     * @throws InvalidInput
     */
    public function bySlug(string $slug, array $request): Calculation
    {
        return $this->handle($this->catalog->resolve($slug), $request);
    }
}
