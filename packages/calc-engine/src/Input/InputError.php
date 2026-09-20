<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Input;

/**
 * یک ایراد مشخص روی یک ورودی مشخص.
 */
final readonly class InputError
{
    /**
     * @param  array<string, float|int|string>  $params  پارامترهای پیام، برای بازسازی متن در لایه نمایش
     */
    public function __construct(
        public string $key,
        public InputErrorCode $code,
        public string $message,
        public array $params = [],
    ) {}

    /**
     * @return array{key: string, code: string, message: string, params: array<string, float|int|string>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'code' => $this->code->value,
            'message' => $this->message,
            'params' => $this->params,
        ];
    }
}
