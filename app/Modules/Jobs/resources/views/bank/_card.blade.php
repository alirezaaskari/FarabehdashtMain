@php
    use App\Support\JalaliDate;
@endphp

{{-- کارت ناشناس بانک رزومه: فقط مهارت، شهر، سابقه و بخش ثبت‌شده؛ نه نام، نه معرفی، نه نشانی سطرها. --}}
<article class="flex h-full flex-col gap-4 rounded-xl border border-line bg-surface p-5" data-bank-card>
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="text-h4 text-ink">{{ $card['place'] ?: 'شهر نامشخص' }}</h3>
        <span class="text-label text-muted">
            @if ($card['years'] !== null)
                @fa($card['years']) سال سابقه
            @else
                سابقه نامشخص
            @endif
        </span>
    </div>

    @if ($card['verified_skills'] !== [] || $card['declared_skills'] !== [])
        <ul class="flex list-none flex-wrap gap-2 ps-0" aria-label="مهارت‌ها">
            @foreach ($card['verified_skills'] as $term)
                <li><x-badge tone="primary">{{ $term->name }} · ثبت‌شده</x-badge></li>
            @endforeach
            @foreach ($card['declared_skills'] as $term)
                <li><x-badge>{{ $term->name }}</x-badge></li>
            @endforeach
        </ul>
    @endif

    @if ($card['verified'] !== [])
        <div>
            <p class="text-label font-semibold text-ink">ثبت‌شده در فرابهداشت</p>
            <ul class="mt-2 flex list-none flex-col divide-y divide-line ps-0">
                @foreach ($card['verified'] as $section)
                    @foreach ($section['items'] as $item)
                        <li class="flex flex-wrap items-baseline justify-between gap-2 py-2">
                            <span class="min-w-0 text-label text-body">
                                {{ $item->title }}
                                <span class="text-muted">· {{ $section['label'] }}</span>
                                @if ($item->count > 1) <span class="text-muted">· @fa($item->count) مورد</span> @endif
                                @if ($item->score !== null) <span class="text-muted">· نمره @fa($item->score)٪</span> @endif
                            </span>
                            <span class="text-note text-muted">{{ JalaliDate::short($item->earnedAt) }}</span>
                        </li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @else
        <p class="text-note text-muted">هنوز چیزی در فرابهداشت ثبت نکرده؛ مهارت‌ها به اظهار خود کارجوست.</p>
    @endif

    {{ $slot ?? '' }}
</article>
