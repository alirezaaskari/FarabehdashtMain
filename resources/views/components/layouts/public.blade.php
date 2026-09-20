@props(['title' => null, 'description' => null, 'canonical' => null, 'noindex' => false, 'active' => null])

{{-- صفحات عمومی: همیشه روشن، چون هدفشان ایندکس‌شدن و خوانایی است. --}}

@php
    $nav = [
        'encyclopedia' => ['دانشنامه', '#'],
        'tools' => ['ابزارها', route('tools.index')],
        'chemicals' => ['مواد شیمیایی', '#'],
        'market' => ['فروشگاه', '#'],
        'courses' => ['دوره‌ها', '#'],
        'jobs' => ['کاریابی', '#'],
    ];
@endphp

<x-layouts.base :title="$title" :description="$description" :canonical="$canonical"
                :noindex="$noindex" theme="light">
    <header data-print="hide"
            class="flex h-19 items-center gap-8 border-b border-line bg-surface px-6 md:px-14">
        <a href="/" class="flex h-touch shrink-0 items-center gap-2.5 no-underline hover:no-underline">
            <span class="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-on-primary">
                <x-icon name="shield" :size="18" />
            </span>
            <span class="text-xl font-extrabold tracking-tight text-ink">{{ config('app.name') }}</span>
        </a>

        <nav aria-label="پیمایش اصلی" class="hidden grow items-center gap-6 md:flex">
            @foreach ($nav as $key => [$label, $url])
                <a href="{{ $url }}"
                   @class([
                       'inline-flex h-touch items-center text-sm no-underline hover:no-underline',
                       'font-bold text-primary' => $active === $key,
                       'font-semibold text-ink hover:text-primary' => $active !== $key,
                   ])
                   @if ($active === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex grow items-center justify-end gap-3 md:grow-0">
            <x-button href="#" variant="primary" size="sm">ورود / ثبت‌نام</x-button>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer data-print="hide" class="bg-ink px-6 py-8 md:px-14">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <span class="text-sm text-primary-soft/80">© {{ config('app.name') }} — تمامی حقوق محفوظ است.</span>
            <nav aria-label="پیوندهای حقوقی" class="flex flex-wrap gap-6">
                @foreach (['قوانین', 'حریم خصوصی', 'سلب مسئولیت', 'وضعیت سرویس'] as $link)
                    <a href="#"
                       class="inline-flex h-touch items-center text-sm text-primary-soft/80
                              no-underline hover:text-on-primary">{{ $link }}</a>
                @endforeach
            </nav>
        </div>
    </footer>
</x-layouts.base>
