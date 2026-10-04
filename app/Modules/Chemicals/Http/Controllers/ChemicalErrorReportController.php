<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Http\Controllers;

use App\Modules\Chemicals\Actions\ReportSubstanceError;
use App\Modules\Chemicals\Domain\Enums\ErrorReportTopic;
use App\Modules\Chemicals\Domain\ReportLimitReached;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\PersianDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** فرم «گزارش اشتباه» پایین جدول حدود در صفحه ماده. */
final readonly class ChemicalErrorReportController
{
    public function store(Request $request, string $slug, ReportSubstanceError $report): RedirectResponse
    {
        $substance = Substance::query()->published()->where('slug', $slug)->first()
            ?? throw new NotFoundHttpException('این ماده پیدا نشد.');

        $min = (int) config('chemicals.reports.message_min', 10);
        $max = (int) config('chemicals.reports.message_max', 2000);

        $validated = $request->validateWithBag('report', [
            'topic' => ['required', Rule::enum(ErrorReportTopic::class)],
            'message' => ['required', 'string', 'min:'.$min, 'max:'.$max],
            'source_url' => ['nullable', 'string', 'max:500', 'url:https'],
        ], [
            'topic.*' => 'بخشی را که اشتباه در آن است انتخاب کنید.',
            'message.required' => 'بنویسید چه چیزی اشتباه است.',
            'message.min' => sprintf('توضیح دست‌کم %s نویسه باشد تا مدیر بداند کدام عدد را بررسی کند.', PersianDigits::from($min)),
            'message.max' => sprintf('توضیح حداکثر %s نویسه باشد.', PersianDigits::from($max)),
            'message.*' => 'بنویسید چه چیزی اشتباه است.',
            'source_url.*' => 'نشانی منبع باید کامل و با https:// شروع شود.',
        ]);

        $back = route('chemicals.show', $substance->slug).'#report';

        try {
            $report->handle(
                $substance,
                (int) $request->user()?->getAuthIdentifier(),
                ErrorReportTopic::from($validated['topic']),
                $validated['message'],
                $validated['source_url'] ?? null,
            );
        } catch (ReportLimitReached $limit) {
            return redirect($back)->withInput()->withErrors([
                'message' => sprintf('امروز %s گزارش فرستاده‌اید که سقف روزانه است. فردا دوباره امتحان کنید.', PersianDigits::from($limit->perDay)),
            ], 'report');
        }

        return redirect($back)->with('report_status', 'گزارش شما ثبت شد. مدیر آن را با منبع اصلی مقایسه می‌کند و اگر لازم باشد عدد را اصلاح می‌کند.');
    }
}
