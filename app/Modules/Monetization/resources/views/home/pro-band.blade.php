{{--
    نوار اشتراک حرفه‌ای صفحه اصلی: رایگان در برابر حرفه‌ای، در یک نگاه.

    `$monthly` را composer همین ماژول می‌دهد و وقتی اشتراک فروخته نمی‌شود
    null است؛ آن‌وقت نوار نیست. سهم رایگان همان پیکربندی لایه دسترسی است.
--}}

@if ($monthly !== null && Route::has('monetization.plans'))
    <section aria-labelledby="home-pro" class="border-b border-line px-6 py-16 md:px-gutter md:py-20">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,36rem)] lg:items-center lg:gap-16">
            <div>
                <x-art name="home-pro-band" class="mb-6 h-32 w-auto" />
                <h2 id="home-pro" class="text-h1 text-ink">برای کسی که هر روز با این عددها کار می‌کند.</h2>
                <p class="mt-3 max-w-[34rem] text-copy text-muted">حساب رایگان برای شروع کافی است. وقتی پروژه‌ها و گزارش‌ها زیاد شد، حرفه‌ای شو.</p>
                <div class="mt-7">
                    <x-button :href="route('monetization.plans')" size="lg" class="max-sm:w-full">مقایسه طرح‌ها</x-button>
                </div>
            </div>

            <div class="grid overflow-hidden rounded-xl border border-line sm:grid-cols-2">
                <div class="p-6">
                    <h3 class="text-label font-semibold text-muted">رایگان</h3>
                    <p class="mt-1 text-h3 text-ink">۰ <span class="text-note font-medium text-muted">تومان</span></p>
                    <ul class="mt-4 list-none space-y-2 ps-0 text-note text-body">
                        <li>همه ابزارها و دانشنامه</li>
                        @if ($freeSaves !== null)
                            <li>ذخیره @fa($freeSaves) محاسبه</li>
                        @endif
                        @if ($freeProjects !== null)
                            <li>@fa($freeProjects) پروژه اندازه‌گیری</li>
                        @endif
                    </ul>
                </div>
                <div class="border-line bg-surface-2 p-6 max-sm:border-t sm:border-s">
                    <h3 class="text-label font-semibold text-primary">حرفه‌ای</h3>
                    <p class="mt-1 text-h3 text-ink">{{ $monthly->price()->formatWithoutUnit() }} <span class="text-note font-medium text-muted">تومان ماهانه</span></p>
                    <ul class="mt-4 list-none space-y-2 ps-0 text-note text-body">
                        <li>ذخیره و پروژه بی‌سقف</li>
                        <li>صدور گزارش PDF با کد تأیید</li>
                        <li>اولویت در پرسش از متخصص</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endif
