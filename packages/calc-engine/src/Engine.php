<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine;

use Farabehdasht\CalcEngine\Exception\InvalidInput;
use Farabehdasht\CalcEngine\Exception\UnknownFormula;
use Farabehdasht\CalcEngine\Formulas\DefaultFormulas;
use Farabehdasht\CalcEngine\Input\InputSet;

/**
 * تنها درِ ورودی موتور.
 *
 * مصرف‌کننده هیچ‌وقت compute() را مستقیم صدا نمی‌زند؛ این‌جا ورودی اعتبارسنجی
 * می‌شود، واحد به خروجی می‌چسبد و نتیجه در یک Calculation تغییرناپذیر بسته
 * می‌شود.
 */
final readonly class Engine
{
    public function __construct(private FormulaRegistry $registry) {}

    public static function withDefaultFormulas(): self
    {
        return new self(DefaultFormulas::registry());
    }

    public function registry(): FormulaRegistry
    {
        return $this->registry;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  string|null  $version  نسخه صریح فرمول؛ null یعنی آخرین نسخه
     *
     * @throws UnknownFormula
     * @throws InvalidInput
     */
    public function run(string $formulaId, array $raw, ?string $version = null): Calculation
    {
        $formula = $this->registry->get($formulaId, $version);
        $inputs = InputSet::validate($formula->inputs(), $raw);
        $outcome = $formula->compute($inputs);

        return new Calculation(
            formulaId: $formula->id(),
            formulaVersion: $formula->version(),
            formulaTitle: $formula->title(),
            reference: $formula->reference(),
            inputs: $inputs->all(),
            outputs: $this->withUnits($formula, $outcome),
            notes: $outcome->notes,
            limitations: $formula->limitations(),
        );
    }

    /**
     * @return array<string, Quantity>
     */
    private function withUnits(Formula $formula, Outcome $outcome): array
    {
        $units = $formula->outputs();
        $outputs = [];

        foreach ($units as $key => $unit) {
            if (! array_key_exists($key, $outcome->values)) {
                throw new UnknownFormula(sprintf(
                    'فرمول «%s» خروجی «%s» را اعلام کرده ولی محاسبه نکرده است.',
                    $formula->id(),
                    $key,
                ));
            }

            $outputs[$key] = Quantity::of($outcome->values[$key], $unit);
        }

        return $outputs;
    }
}
