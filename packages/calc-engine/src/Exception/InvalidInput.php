<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Exception;

use Farabehdasht\CalcEngine\Input\InputError;
use InvalidArgumentException;

/**
 * ورودی‌های نامعتبر — همه با هم، نه یکی‌یکی.
 *
 * موتور اولین ایراد را پرت نمی‌کند و همه را جمع می‌کند تا کاربر یک بار فرم را
 * اصلاح کند نه چند بار.
 */
final class InvalidInput extends InvalidArgumentException implements CalcEngineException
{
    /**
     * @param  list<InputError>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(
            implode(' ', array_map(static fn (InputError $e): string => $e->message, $errors)),
        );
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_values(array_unique(array_map(
            static fn (InputError $e): string => $e->key,
            $this->errors,
        )));
    }
}
