<x-layouts.print :title="$calculation->label ?? 'گزارش محاسبه'">

    {{-- نسخه چاپی: بدون منو، بدون دکمه. هرچه لازم است برای بازبینی مستقل،
         روی همین برگه است: ورودی، خروجی، رابطه، منبع و نسخه. --}}

    <header class="flex items-start justify-between border-b border-line pb-4">
        <div>
            <h1 class="text-h2 font-extrabold text-ink">
                {{ $calculation->label ?? ($tool?->definition->title ?? $calculation->tool_slug) }}
            </h1>
            @if ($tool !== null && $calculation->label !== null)
                <p class="mt-1 text-label text-muted">{{ $tool->definition->title }}</p>
            @endif
        </div>

        <div class="text-left text-note text-muted">
            <p>{{ config('app.name') }}</p>
            <p>{{ \App\Support\JalaliDate::longWithTime($calculation->created_at) }}</p>
        </div>
    </header>

    <section class="mt-6">
        <h2 class="text-h4 font-extrabold text-ink">داده اندازه‌گیری</h2>
        <table class="mt-2 w-full text-label">
            <tbody>
                @foreach ($inputs as $row)
                    <tr class="border-b border-line">
                        <th scope="row" class="py-1.5 text-start font-normal text-muted">{{ $row->label }}</th>
                        <td class="py-1.5 text-end font-bold text-ink" dir="ltr" data-numeric>
                            {{ $row->value }} {{ $row->unit }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="mt-6">
        <h2 class="text-h4 font-extrabold text-ink">نتیجه</h2>
        <table class="mt-2 w-full text-label">
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-b border-line">
                        <th scope="row" class="py-1.5 text-start font-normal text-muted">{{ $row->label }}</th>
                        <td class="py-1.5 text-end font-bold text-ink" dir="ltr" data-numeric>
                            {{ $row->value }} {{ $row->unit }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    @if ($calculation->notes !== [])
        <section class="mt-6">
            <h2 class="text-h4 font-extrabold text-ink">یادداشت‌ها</h2>
            <ul class="mt-2 list-disc ps-5 text-label text-muted">
                @foreach ($calculation->notes as $note)
                    <li class="mt-1">{{ $note }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($tool !== null)
        <section class="mt-6">
            <h2 class="text-h4 font-extrabold text-ink">رابطه و منبع</h2>
            <p class="mt-2 text-label text-muted" dir="ltr" data-numeric>
                {{ $tool->formula->reference()->relation }}
            </p>
            <p class="mt-1 text-label text-muted">
                <span dir="ltr" data-numeric>{{ $tool->formula->reference()->title }}</span>
                — {{ $tool->formula->reference()->publisher }}، @fa($tool->formula->reference()->year)
            </p>
        </section>

        <section class="mt-6">
            <h2 class="text-h4 font-extrabold text-ink">محدودیت‌ها</h2>
            <ul class="mt-2 list-disc ps-5 text-label text-muted">
                @foreach ($tool->formula->limitations() as $limitation)
                    <li class="mt-1">{{ $limitation }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <footer class="mt-8 border-t border-line pt-4">
        <p class="text-note text-caution-ink">
            {{ \Farabehdasht\CalcEngine\Calculation::DISCLAIMER }}
        </p>
        <p class="mt-2 text-note text-muted" dir="ltr" data-numeric>
            {{ $calculation->formula_id.'@'.$calculation->formula_version }} ·
            {{ $calculation->uuid }} ·
            {{ $reproducible ? 'reproduced-ok' : 'reproduction-mismatch' }}
        </p>
    </footer>

</x-layouts.print>
