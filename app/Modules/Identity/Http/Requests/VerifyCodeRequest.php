<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class VerifyCodeRequest extends FormRequest
{
    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.required' => 'کد تأیید را وارد کنید.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // کاربر ممکن است کد را با ارقام فارسی بنویسد.
        $this->merge([
            'code' => str_replace(
                ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
                ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                trim((string) $this->input('code')),
            ),
        ]);
    }

    public function code(): string
    {
        return (string) $this->input('code');
    }
}
