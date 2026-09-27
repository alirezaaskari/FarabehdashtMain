@php
    use App\Support\JalaliDate;
    use App\Support\PersianDigits;
@endphp

{{-- بخش «ثبت‌شده در فرابهداشت»: فقط چیزهایی که خود سایت ثبت کرده است. --}}
<section aria-labelledby="verified-heading">
    <div class="flex flex-wrap items-center gap-3">
        <h2 id="verified-heading" class="text-h3 text-ink">ثبت‌شده در فرابهداشت</h2>
        <x-badge tone="primary">ثبت خودکار</x-badge>
    </div>
    <p class="mt-1 text-copy text-muted">از کارهایی که در همین سایت انجام شده جمع می‌شود و کاربر نمی‌تواند آن را دستی عوض کند.</p>

    @if ($verified === [])
        <x-empty-state art="empty-passport" icon="badge" class="mt-4" title="هنوز چیزی ثبت نشده"
                       :description="'دوره‌ای را تا آخر ببینید، آزمون زمان‌دار را با نمره '.PersianDigits::from($examMin).'٪ یا بیشتر بگذرانید، گزارش اندازه‌گیری صادر کنید یا به پرسش تخصصی پاسخ دهید؛ همین‌جا می‌آید.'" />
    @else
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            @foreach ($verified as $section)
                <x-card :title="$section['label']" heading="text-h4">
                    <ul class="flex list-none flex-col divide-y divide-line ps-0">
                        @foreach ($section['items'] as $item)
                            <li class="flex flex-wrap items-baseline justify-between gap-2 py-2.5">
                                <span class="min-w-0 text-label text-body">
                                    @if ($item->url)
                                        <a href="{{ $item->url }}">{{ $item->title }}</a>
                                    @else
                                        {{ $item->title }}
                                    @endif
                                    @if ($item->count > 1) <span class="text-muted">· @fa($item->count) مورد</span> @endif
                                    @if ($item->score !== null) <span class="text-muted">· نمره @fa($item->score)٪</span> @endif
                                </span>
                                <span class="text-note text-muted">{{ JalaliDate::short($item->earnedAt) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endforeach
        </div>
    @endif

    @if ($verifiedSkills !== [])
        <p class="mt-4 text-label font-semibold text-ink">مهارت‌هایی که پشتوانه ثبت‌شده دارند</p>
        <ul class="mt-2 flex list-none flex-wrap gap-2 ps-0">
            @foreach ($verifiedSkills as $term)
                <li><x-badge tone="primary">{{ $term->name }}</x-badge></li>
            @endforeach
        </ul>
    @endif
</section>
