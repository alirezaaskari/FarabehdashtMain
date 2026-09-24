{{--
    فوتر مشترک: نوار تیره با همان گاتر افقی سربرگ.

    صفحات حقوقی و وضعیت سرویس را ماژول میزکار می‌دهد؛ هر پیوند با Route::has
    محافظت شده تا خاموش‌شدن آن ماژول فوتر را نشکند (قاعده ۲).
--}}

@php
    $links = array_values(array_filter([
        Route::has('workspace.legal.show') ? ['قوانین', route('workspace.legal.show', 'terms')] : null,
        Route::has('workspace.legal.show') ? ['حریم خصوصی', route('workspace.legal.show', 'privacy')] : null,
        Route::has('workspace.legal.show') ? ['سلب مسئولیت', route('workspace.legal.show', 'disclaimer')] : null,
        Route::has('workspace.legal.show') ? ['تماس با ما', route('workspace.legal.show', 'contact')] : null,
        Route::has('workspace.status') ? ['وضعیت سرویس', route('workspace.status')] : null,
    ]));
@endphp

<footer data-print="hide" class="mt-auto shrink-0 bg-ink px-6 py-8 md:px-gutter">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <span class="text-label text-primary-soft/80">© {{ config('app.name') }} — تمامی حقوق محفوظ است.</span>

        @if ($links !== [])
            <nav aria-label="پیوندهای حقوقی" class="flex flex-wrap gap-5">
                @foreach ($links as [$label, $url])
                    <a href="{{ $url }}"
                       class="inline-flex h-touch items-center text-label text-primary-soft/80
                              no-underline hover:text-on-primary">{{ $label }}</a>
                @endforeach
            </nav>
        @endif
    </div>
</footer>
