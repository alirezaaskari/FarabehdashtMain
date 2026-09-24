{{-- فوتر مشترک: نوار تیره با همان گاتر افقی سربرگ. --}}

<footer data-print="hide" class="mt-auto shrink-0 bg-ink px-6 py-8 md:px-gutter">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <span class="text-label text-primary-soft/80">© {{ config('app.name') }} — تمامی حقوق محفوظ است.</span>

        <nav aria-label="پیوندهای حقوقی" class="flex flex-wrap gap-5">
            @foreach (['قوانین', 'حریم خصوصی', 'سلب مسئولیت', 'وضعیت سرویس'] as $link)
                <a href="#"
                   class="inline-flex h-touch items-center text-label text-primary-soft/80
                          no-underline hover:text-on-primary">{{ $link }}</a>
            @endforeach
        </nav>
    </div>
</footer>
