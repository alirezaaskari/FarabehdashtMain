@props(['headers' => [], 'caption' => null])

{{--
    جدول داده — همان قاب پروتوتایپ: سربرگ روی سطح دوم، خط جداکننده ملایم
    بین ردیف‌ها و یک ردیف پانویس اختیاری.

    برخلاف پروتوتایپ که با grid ساخته شده، اینجا جدول واقعی است تا صفحه‌خوان
    رابطه ستون و سلول را بفهمد. ظاهر یکی است.

    روی موبایل جدول افقی اسکرول می‌شود؛ ستون‌ها فشرده نمی‌شوند چون عدد
    اندازه‌گیری نباید بشکند. min-w-xl همین را تضمین می‌کند: بدون آن جدول
    w-full خودش را به عرض گوشی فشرده می‌کرد و هر سلول چند خط می‌شد.
--}}

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg border border-line bg-surface']) }}>
    <table class="w-full min-w-xl border-collapse text-start">
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif

        <thead class="border-b border-line bg-surface-2">
            <tr>
                @foreach ($headers as $header)
                    <th scope="col" class="px-4 py-3.5 text-start text-note font-bold whitespace-nowrap text-ink">
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody class="[&_td]:px-4 [&_td]:py-3.5 [&_td]:text-label [&_td]:text-body
                       [&_tr]:border-b [&_tr]:border-line-soft [&_tr:last-child]:border-0">
            {{ $slot }}
        </tbody>

        @isset($footnote)
            <tfoot>
                <tr class="border-t border-line bg-surface-2">
                    <td colspan="{{ count($headers) }}" class="px-4 py-3.5 text-note text-muted">
                        {{ $footnote }}
                    </td>
                </tr>
            </tfoot>
        @endisset
    </table>
</div>
