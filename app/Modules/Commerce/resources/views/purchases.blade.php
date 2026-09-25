@use(App\Support\JalaliDate)

<x-layouts.workspace title="خریدهای من"
                     heading="خریدهای من"
                     lede="فایل‌هایی که خریده‌اید؛ دانلود همیشه آخرین نسخه تأییدشده را می‌دهد."
                     nav="purchases">

    @if ($items->isEmpty())
        <x-empty-state icon="bag"
                       title="هنوز فایلی نخریده‌اید"
                       description="فایل‌ها و قالب‌هایی که می‌خرید این‌جا می‌مانند و هر نسخه تازه‌شان در دسترس است.">
            @if (Route::has('commerce.index'))
                <x-slot:action>
                    <x-button :href="route('commerce.index')" variant="primary">رفتن به فروشگاه</x-button>
                </x-slot:action>
            @endif
        </x-empty-state>
    @else
        <ul class="flex list-none flex-col gap-3.5 ps-0">
            @foreach ($items as $item)
                @php($version = $item->product->latestVersion())

                <li class="flex flex-col gap-4 rounded-xl border border-line bg-surface p-5 md:flex-row md:items-center">
                    <div class="min-w-0 grow">
                        <h2 class="text-h4 text-ink">{{ $item->product->title }}</h2>
                        <p class="mt-1.5 text-label text-muted">
                            @if ($item->order->paid_at)
                                خرید در {{ JalaliDate::short($item->order->paid_at) }} ·
                            @endif
                            {{ $item->unitPrice()->format() }}
                            @if ($version)
                                · نسخه <span dir="ltr" data-numeric>{{ $version->version }}</span>
                            @endif
                        </p>
                    </div>

                    @if ($version)
                        <x-button :href="URL::temporarySignedRoute('commerce.download', $linkExpiry, ['product' => $item->product])"
                                  variant="primary" icon="file" class="shrink-0">
                            دانلود
                        </x-button>
                    @else
                        <span class="text-label text-muted">فایلی برای دانلود ثبت نشده</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

</x-layouts.workspace>
