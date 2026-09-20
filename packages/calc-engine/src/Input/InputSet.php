<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Input;

use Farabehdasht\CalcEngine\Exception\InvalidInput;
use Farabehdasht\CalcEngine\Quantity;

/**
 * ورودی‌های اعتبارسنجی‌شده یک محاسبه.
 *
 * ساختن این شیء فقط از راه validate() ممکن است، پس هر فرمولی که یک InputSet
 * می‌گیرد مطمئن است هر کلید لازم هست، عدد است، متناهی است و در بازه است.
 */
final readonly class InputSet
{
    /**
     * @param  array<string, Quantity|list<Quantity>>  $values
     */
    private function __construct(private array $values) {}

    /**
     * @param  array<string, InputDefinition>  $definitions
     * @param  array<string, mixed>  $raw
     *
     * @throws InvalidInput
     */
    public static function validate(array $definitions, array $raw): self
    {
        $errors = [];
        $values = [];

        foreach (array_keys($raw) as $key) {
            if (! isset($definitions[$key])) {
                $errors[] = new InputError(
                    (string) $key,
                    InputErrorCode::Unexpected,
                    sprintf('ورودی ناشناخته «%s» برای این فرمول فرستاده شد.', (string) $key),
                );
            }
        }

        foreach ($definitions as $key => $definition) {
            if (! array_key_exists($key, $raw)) {
                $errors[] = new InputError(
                    $key,
                    InputErrorCode::Missing,
                    sprintf('«%s» الزامی است.', $definition->label),
                );

                continue;
            }

            $value = self::cast($definition, $raw[$key], $errors);

            if ($value !== null) {
                $values[$key] = $value;
            }
        }

        if ($errors !== []) {
            throw new InvalidInput($errors);
        }

        return new self($values);
    }

    /**
     * @throws InvalidInput
     */
    public function value(string $key): float
    {
        $quantity = $this->values[$key] ?? null;

        if (! $quantity instanceof Quantity) {
            throw new InvalidInput([new InputError(
                $key,
                InputErrorCode::ExpectedSingle,
                sprintf('ورودی «%s» یک مقدار تکی نیست.', $key),
            )]);
        }

        return $quantity->value;
    }

    /**
     * @return list<float>
     *
     * @throws InvalidInput
     */
    public function values(string $key): array
    {
        $quantities = $this->values[$key] ?? null;

        if (! is_array($quantities)) {
            throw new InvalidInput([new InputError(
                $key,
                InputErrorCode::ExpectedList,
                sprintf('ورودی «%s» یک فهرست نیست.', $key),
            )]);
        }

        return array_map(static fn (Quantity $q): float => $q->value, $quantities);
    }

    /**
     * @return array<string, Quantity|list<Quantity>>
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * @param  list<InputError>  $errors
     * @return Quantity|list<Quantity>|null
     */
    private static function cast(InputDefinition $definition, mixed $raw, array &$errors): Quantity|array|null
    {
        if ($definition->list) {
            return self::castList($definition, $raw, $errors);
        }

        if (is_array($raw)) {
            $errors[] = new InputError(
                $definition->key,
                InputErrorCode::ExpectedSingle,
                sprintf('«%s» یک مقدار می‌گیرد، نه فهرست.', $definition->label),
            );

            return null;
        }

        return self::castNumber($definition, $raw, $definition->key, $errors);
    }

    /**
     * @param  list<InputError>  $errors
     * @return list<Quantity>|null
     */
    private static function castList(InputDefinition $definition, mixed $raw, array &$errors): ?array
    {
        if (! is_array($raw)) {
            $errors[] = new InputError(
                $definition->key,
                InputErrorCode::ExpectedList,
                sprintf('«%s» فهرستی از مقادیر می‌گیرد.', $definition->label),
            );

            return null;
        }

        $count = count($raw);

        if ($count < $definition->minItems) {
            $errors[] = new InputError(
                $definition->key,
                InputErrorCode::TooFewItems,
                sprintf('«%s» دست‌کم %d مقدار می‌خواهد.', $definition->label, $definition->minItems),
                ['min_items' => $definition->minItems, 'given' => $count],
            );

            return null;
        }

        if ($count > $definition->maxItems) {
            $errors[] = new InputError(
                $definition->key,
                InputErrorCode::TooManyItems,
                sprintf('«%s» بیش از %d مقدار نمی‌پذیرد.', $definition->label, $definition->maxItems),
                ['max_items' => $definition->maxItems, 'given' => $count],
            );

            return null;
        }

        $quantities = [];

        foreach (array_values($raw) as $index => $item) {
            $quantity = self::castNumber(
                $definition,
                $item,
                sprintf('%s.%d', $definition->key, $index),
                $errors,
            );

            if ($quantity === null) {
                return null;
            }

            $quantities[] = $quantity;
        }

        return $quantities;
    }

    /**
     * @param  list<InputError>  $errors
     */
    private static function castNumber(
        InputDefinition $definition,
        mixed $raw,
        string $errorKey,
        array &$errors,
    ): ?Quantity {
        if (! is_int($raw) && ! is_float($raw)) {
            $errors[] = new InputError(
                $errorKey,
                InputErrorCode::NotNumeric,
                sprintf('«%s» باید عدد باشد.', $definition->label),
            );

            return null;
        }

        $number = (float) $raw;

        if (! is_finite($number)) {
            $errors[] = new InputError(
                $errorKey,
                InputErrorCode::NotFinite,
                sprintf('«%s» باید عددی متناهی باشد.', $definition->label),
            );

            return null;
        }

        if ($number < $definition->min || $number > $definition->max) {
            $errors[] = new InputError(
                $errorKey,
                InputErrorCode::OutOfRange,
                sprintf(
                    '«%s» باید بین %s و %s %s باشد.',
                    $definition->label,
                    self::number($definition->min),
                    self::number($definition->max),
                    $definition->unit->label(),
                ),
                ['min' => $definition->min, 'max' => $definition->max, 'given' => $number],
            );

            return null;
        }

        return Quantity::of($number, $definition->unit);
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
