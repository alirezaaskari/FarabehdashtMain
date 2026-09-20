<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine;

use Farabehdasht\CalcEngine\Exception\UnknownFormula;

/**
 * نتیجه یک محاسبه — تغییرناپذیر و خودبسنده.
 *
 * زمان این‌جا نیست: موتور ساعت نمی‌خواند تا خالص و قابل تست طلایی بماند؛
 * مهر زمان کار لایه‌ای است که محاسبه را ذخیره می‌کند.
 *
 * toArray() همه‌چیزِ لازم برای بازتولید را دارد (شناسه و نسخه فرمول و ورودی‌ها)
 * تا محاسبه ذخیره‌شده بعدها با همان نسخه دوباره اجرا شود، نه با نسخه روز.
 */
final readonly class Calculation
{
    /**
     * سلب ادعا — روی هر محاسبه‌ای می‌آید، بی‌استثنا.
     */
    public const string DISCLAIMER = 'این خروجی یک محاسبه عددی است و تشخیص پزشکی، تأیید ایمنی یا انطباق قانونی محسوب نمی‌شود.';

    /**
     * @param  array<string, Quantity|list<Quantity>>  $inputs
     * @param  array<string, Quantity>  $outputs
     * @param  list<string>  $notes
     * @param  list<string>  $limitations
     */
    public function __construct(
        public string $formulaId,
        public string $formulaVersion,
        public string $formulaTitle,
        public Reference $reference,
        public array $inputs,
        public array $outputs,
        public array $notes,
        public array $limitations,
    ) {}

    /**
     * @throws UnknownFormula
     */
    public function output(string $key): Quantity
    {
        return $this->outputs[$key]
            ?? throw new UnknownFormula(sprintf(
                'فرمول «%s» خروجی‌ای به نام «%s» ندارد.',
                $this->formulaId,
                $key,
            ));
    }

    /**
     * سلب ادعای عمومی به‌علاوه محدودیت‌های خود این رابطه.
     *
     * @return list<string>
     */
    public function disclaimers(): array
    {
        return [self::DISCLAIMER, ...$this->limitations];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'formula' => [
                'id' => $this->formulaId,
                'version' => $this->formulaVersion,
                'title' => $this->formulaTitle,
                'reference' => $this->reference->toArray(),
            ],
            'inputs' => $this->flatten($this->inputs),
            'outputs' => $this->flatten($this->outputs),
            'notes' => $this->notes,
            'disclaimers' => $this->disclaimers(),
        ];
    }

    /**
     * @param  array<string, Quantity|list<Quantity>>  $quantities
     * @return array<string, mixed>
     */
    private function flatten(array $quantities): array
    {
        return array_map(
            static fn (Quantity|array $value): array => is_array($value)
                ? array_map(static fn (Quantity $q): array => $q->toArray(), $value)
                : $value->toArray(),
            $quantities,
        );
    }
}
