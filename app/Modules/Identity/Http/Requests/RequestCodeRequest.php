<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Support\Mobile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class RequestCodeRequest extends FormRequest
{
    /** @return array<string, list<ValidationRule|string>> */
    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'max:20'],
            'terms' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'mobile.required' => 'شماره موبایل را وارد کنید.',
            'terms.accepted' => 'برای ادامه باید قوانین استفاده و سیاست حریم خصوصی را بپذیرید.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mobile' => trim((string) $this->input('mobile'))]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! Mobile::isValid((string) $this->input('mobile'))) {
                $validator->errors()->add('mobile', 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.');
            }
        });
    }

    public function mobile(): Mobile
    {
        return Mobile::fromInput((string) $this->input('mobile'));
    }
}
