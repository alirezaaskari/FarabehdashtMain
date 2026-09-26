{{--
    نوار اشتراک حرفه‌ای صفحه اصلی: رایگان در برابر حرفه‌ای، در یک نگاه.

    `$monthly` را composer همین ماژول می‌دهد و وقتی اشتراک فروخته نمی‌شود
    null است؛ آن‌وقت نوار نیست. سهم رایگان همان پیکربندی لایه دسترسی است.
--}}

@if ($monthly !== null && Route::has('monetization.plans'))
    <section aria-labelledby="home-pro" class="px-6 py-14 md:px-gutter">
        <div class="grid gap-8 rounded-xl bg-ink p-7 text-on-primary md:p-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)] lg:items-center lg:gap-12">
            <div>
                <span class="text-label font-bold text-focus">اشتراک حرفه‌ای</span>
                <h2 id="home-pro" class="mt-2 text-h1">برای کسی که هر روز با این عددها کار می‌کند.</h2>
                <p class="mt-3 text-copy text-line">حساب رایگان برای شروع کافی است. وقتی پروژه‌ها و گزارش‌ها زیاد شد، حرفه‌ای شو.</p>
                <div class="mt-6">
                    <x-button :href="route('monetization.plans')" variant="on-dark" size="lg" class="max-sm:w-full">مقایسه طرح‌ها</x-button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-note border border-body p-5">
                    <h3 class="text-label font-bold text-line">رایگان</h3>
                    <ul class="mt-3 list-none space-y-1.5 ps-0 text-note text-line">
                        <li>همه ابزارها و دانشنامه</li>
                        @if ($freeSaves !== null)
                            <li>ذخیره @fa($freeSaves) محاسبه</li>
                        @endif
                        @if ($freeProjects !== null)
                            <li>@fa($freeProjects) پروژه اندازه‌گیری</li>
                        @endif
                    </ul>
                </div>
                <div class="rounded-note bg-primary p-5">
                    <h3 class="text-label font-bold text-primary-soft">حرفه‌ای</h3>
                    <p class="mt-1 text-h3">{{ $monthly->price()->formatWithoutUnit() }} <span class="text-note font-semibold">تومان ماهانه</span></p>
                    <ul class="mt-3 list-none space-y-1.5 ps-0 text-note text-primary-soft">
                        <li>ذخیره و پروژه بی‌سقف</li>
                        <li>صدور گزارش PDF با کد تأیید</li>
                        <li>اولویت در پرسش از متخصص</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endif
