<?php

declare(strict_types=1);

namespace App\Modules\Reports\Http\Controllers;

use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\TrackingCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * صفحه عمومی تأیید اصالت (DEC-28).
 *
 * فقط فراداده و هش نشان داده می‌شود، نه محتوا، نه نام کارفرما و نه فایل:
 * گیرنده خودش سند را دارد و فقط می‌خواهد بداند دست‌کاری نشده. هر چیز
 * بیشتر، داده کارفرما را در اختیار هر کسی می‌گذاشت که شناسه را دیده باشد.
 */
final readonly class VerificationController
{
    public function form(Request $request): View|RedirectResponse
    {
        $input = trim((string) $request->query('code', ''));

        if ($input === '') {
            return view('reports::verify.form', ['input' => '', 'invalid' => false]);
        }

        $code = TrackingCode::parse($input);

        return $code === null
            ? view('reports::verify.form', ['input' => $input, 'invalid' => true])
            : redirect()->route('reports.verify.show', $code->value);
    }

    public function show(string $code): View|RedirectResponse
    {
        $parsed = TrackingCode::parse($code);

        if ($parsed === null) {
            return view('reports::verify.form', ['input' => $code, 'invalid' => true]);
        }

        // شکل استاندارد در نشانی، تا یک سند یک نشانی داشته باشد.
        if ($parsed->value !== $code) {
            return redirect()->route('reports.verify.show', $parsed->value);
        }

        $report = Report::query()->issued()->where('tracking_code', $parsed->value)->with('supersededBy')->first();

        return view('reports::verify.show', [
            'code' => $parsed->value,
            'report' => $report,
            'document' => $report?->document(),
        ]);
    }
}
