@php
    use App\Support\JalaliDate;
@endphp

<x-layouts.print :title="$article->title">

    {{-- نسخه چاپی: بدون منو، بدون دکمه، بدون محتوای مرتبط.
         هرچه لازم است برای استناد مستقل، روی همین برگه است: نوع، عنوان،
         نویسنده، بازبین، هر دو تاریخ، متن کامل، و فهرست منابع نسخه‌دار. --}}

    <header class="flex items-start justify-between border-b-2 border-primary pb-3">
        <span class="flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-primary text-on-primary">
                <x-icon name="shield" :size="14" />
            </span>
            <span class="text-sm font-extrabold text-ink">{{ config('app.name') }} — دانشنامه تخصصی</span>
        </span>

        <span class="text-xs text-muted">چاپ‌شده در {{ JalaliDate::short($printedAt) }}</span>
    </header>

    <span class="mt-5 block text-xs font-bold text-caution">{{ $article->type->label() }}</span>

    <h1 class="mt-2 text-2xl font-extrabold leading-relaxed text-ink">{{ $article->title }}</h1>

    <p class="mt-3 text-sm text-body">{{ $article->summary }}</p>

    <dl class="mt-4 grid grid-cols-2 gap-2 rounded-lg bg-surface-2 px-4 py-3">
        @foreach ([
            ['نویسنده', $article->author?->name ?? 'ثبت نشده'],
            ['بازبین علمی', $article->reviewer?->name ?? 'ثبت نشده'],
            ['آخرین بازبینی', $article->reviewed_at ? JalaliDate::short($article->reviewed_at) : 'ثبت نشده'],
            ['بازبینی بعدی', $article->review_due_at ? JalaliDate::short($article->review_due_at) : 'ثبت نشده'],
        ] as [$label, $value])
            <div class="flex gap-1.5">
                <dt class="text-xs text-muted">{{ $label }}:</dt>
                <dd class="text-xs font-bold text-ink">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @foreach ($article->sections as $section)
        <section>
            <h2 class="mt-6 text-sm font-extrabold text-primary">
                @fa($section->position). {{ $section->heading }}
            </h2>

            @foreach ($section->paragraphs() as $paragraph)
                <p class="mt-2 text-xs leading-7 text-body">{{ $paragraph }}</p>
            @endforeach

            @if ($section->note)
                <div class="mt-3 rounded-e-lg border-s-[3px] border-primary bg-surface-2 px-4 py-3">
                    <span class="block text-xs font-bold text-primary-deep">نکته کلیدی</span>
                    <span class="mt-1 block text-xs leading-7 text-body">{{ $section->note }}</span>
                </div>
            @endif
        </section>
    @endforeach

    {{-- منابع در نسخه چاپی اجباری‌اند: برگه‌ای که منبعش را نگوید، قابل
         استناد نیست و همان چیزی است که معیار پذیرش این بخش می‌خواهد. --}}
    <section>
        <h2 class="mt-6 text-sm font-extrabold text-primary">منابع</h2>

        @if ($article->references->isEmpty())
            <p class="mt-2 text-xs text-muted">برای این محتوا منبعی ثبت نشده است.</p>
        @else
            <ol class="mt-2 flex flex-col gap-1.5 ps-5">
                @foreach ($article->references as $reference)
                    <li class="text-xs leading-7 text-body">
                        <span dir="auto">{{ $reference->title }}</span>
                        @if ($reference->publisher)
                            — {{ $reference->publisher }}
                        @endif
                        @if ($reference->version())
                            <span @if ($reference->usesLatinScript()) dir="ltr" data-numeric @endif
                            >({{ $reference->version() }})</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <x-disclaimer class="mt-6" size="sm">
        این مطلب خلاصه و بومی‌سازی‌شده منابع بالاست و جایگزین متن اصلی استاندارد نیست.
        ادعای انطباق قانونی قطعی یا تشخیص پزشکی ندارد.
    </x-disclaimer>

    <footer class="mt-5 border-t border-line pt-3">
        <span class="text-xs text-muted" dir="ltr" data-numeric>
            {{ route('encyclopedia.show', $article->slug) }}
        </span>
    </footer>

</x-layouts.print>
