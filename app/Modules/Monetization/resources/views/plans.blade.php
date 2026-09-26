@php
    use App\Modules\Monetization\Domain\Enums\BillingCycle;
    use App\Support\JalaliDate;
    use App\Support\PersianNumber;

    // سه ستون پروتوتایپ: رایگان، ماهانه (پیشنهاد ما) و سالانه. کاربر فقط وقتی
    // اشتراک می‌خرد که ببیند در برابر پلن رایگان چه می‌گیرد.
    $saves = $freeLimits['save_calculation'] ?? null;
    $projects = $freeLimits['create_project'] ?? null;

    $free = array_values(array_filter([
        ['دانشنامه کامل', true],
        ['بانک مواد شیمیایی', true],
        ['همه ابزارهای محاسبه', true],
        $saves !== null ? ['ذخیره '.PersianNumber::format((int) $saves).' محاسبه', true] : null,
        $projects !== null ? [PersianNumber::format((int) $projects).' پروژه اندازه‌گیری', true] : null,
        ['صدور گزارش PDF با کد تأیید', false],
        $discountPercent > 0 ? ['تخفیف فروشگاه', false] : null,
    ]));

    $pro = array_values(array_filter([
        ['همه امکانات رایگان', true],
        ['ذخیره نامحدود محاسبه', true],
        ['پروژه اندازه‌گیری نامحدود', true],
        ['صدور گزارش PDF با کد تأیید', true],
        ['بایگانی نسخه‌های گزارش', true],
        $discountPercent > 0 ? [PersianNumber::percent($discountPercent).' تخفیف روی فایل‌های فروشگاه', true] : null,
    ]));

    $compare = array_values(array_filter([
        ['ابزارهای محاسباتی', 'همه', 'همه'],
        $saves !== null ? ['ذخیره محاسبه', PersianNumber::format((int) $saves).' مورد', 'نامحدود'] : null,
        $projects !== null ? ['پروژه اندازه‌گیری', PersianNumber::format((int) $projects).' پروژه', 'نامحدود'] : null,
        ['گزارش PDF با کد تأیید', null, 'نامحدود'],
        $discountPercent > 0 ? ['تخفیف فروشگاه', null, PersianNumber::percent($discountPercent)] : null,
    ]));

    $isCurrent = $subscription?->isCurrent() ?? false;
@endphp

<x-layouts.public title="اشتراک حرفه‌ای"
                  description="ذخیره نامحدود محاسبه و پروژه اندازه‌گیری، گزارش PDF و تخفیف فروشگاه؛ ماهانه یا سالانه."
                  active="pro">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['خانه', route('home')], ['اشتراک حرفه‌ای', null]]" />
    </x-slot:breadcrumb>

    <x-page-header title="اشتراک حرفه‌ای فرابهداشت"
                   lede="کارشناس بهداشت حرفه‌ای هر ماه گزارش می‌نویسد. اشتراک Pro ذخیره نامحدود، گزارش‌ساز و پروژه‌های اندازه‌گیری را باز می‌کند." />

    @if ($isCurrent)
        <x-alert tone="success" title="اشتراک شما فعال است" class="mt-6">
            تا {{ $subscription->ends_at === null ? '—' : JalaliDate::long($subscription->ends_at) }} فعال است.
            @if ($subscription->cancelled_at !== null)
                تمدید خودکار متوقف شده و پس از این تاریخ به پلن رایگان برمی‌گردید.
            @endif
        </x-alert>
    @endif

    <div class="mt-8 grid items-stretch gap-5 lg:grid-cols-3">
        {{-- رایگان --}}
        <x-card size="lg" class="flex flex-col">
            <h2 class="text-h3 text-ink">رایگان</h2>
            <p class="mt-4 flex items-baseline gap-2">
                <span class="text-stat text-ink">@fa(0)</span>
                <span class="text-note text-muted">تومان</span>
            </p>

            <x-monetization::feature-list :items="$free" class="mt-6" />

            <div class="mt-auto pt-8">
                @guest
                    <x-button :href="Route::has('login') ? route('login') : route('home')" variant="secondary" block>ساخت حساب رایگان</x-button>
                @else
                    <x-button variant="secondary" block disabled>{{ $hasAccess ? 'همراه با اشتراک' : 'پلن فعلی شما' }}</x-button>
                @endguest
            </div>
        </x-card>

        @foreach ($plans as $plan)
            @php $featured = $plan->billing_cycle === BillingCycle::Monthly; @endphp

            <section @class([
                'flex flex-col rounded-xl border px-6 py-8 md:px-9',
                'border-primary ring-1 ring-primary bg-surface' => $featured,
                'border-line bg-surface' => ! $featured,
            ])>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-h3 text-ink">{{ $plan->title }}</h2>

                    @if ($featured)
                        <x-badge tone="primary">پیشنهاد ما</x-badge>
                    @elseif ($plan->billing_cycle === BillingCycle::Yearly && $freeMonths > 0)
                        <x-badge tone="primary">@fa($freeMonths) ماه رایگان</x-badge>
                    @endif
                </div>

                <p class="mt-4 flex items-baseline gap-2">
                    <span class="text-stat text-ink">{{ $plan->price()->formatWithoutUnit() }}</span>
                    <span class="text-note text-muted">
                        تومان / {{ $plan->billing_cycle->label() }}
                    </span>
                </p>

                <x-monetization::feature-list :items="$pro" class="mt-6" />

                <div class="mt-auto pt-8">
                    @auth
                        <form method="POST" action="{{ route('monetization.checkout', $plan->slug) }}">
                            @csrf
                            <x-payment-method :total="$plan->price()" class="mb-4" />
                            <x-button type="submit" :variant="$featured ? 'primary' : 'secondary'" block>
                                {{ $hasAccess ? 'تمدید این پلن' : 'خرید این پلن' }}
                            </x-button>
                        </form>
                    @else
                        <x-button :href="Route::has('login') ? route('login') : route('home')" :variant="$featured ? 'primary' : 'secondary'" block>
                            ورود و خرید
                        </x-button>
                    @endauth
                </div>
            </section>
        @endforeach
    </div>

    {{-- تصویر واقعی خروجی به‌جای توضیح: هر دو از همین سایت با داده نمونه گرفته
         شده‌اند. فایل‌ها در public/images/pro هستند و هیچ منبع بیرونی بار نمی‌شود. --}}
    <section aria-labelledby="pro-preview" class="mt-14 border-t border-line pt-8">
        <h2 id="pro-preview" class="text-h2 text-ink">آنچه با اشتراک می‌سازید</h2>
        <p class="mt-2 text-copy text-muted">نمونه واقعی از خروجی سایت؛ نام‌ها و عددها ساختگی‌اند.</p>

        <div class="mt-6 grid items-start gap-8 lg:grid-cols-2">
            @foreach ([
                ['report-sample.webp', 1071, 875, 'نمونه صفحه اول گزارش PDF: مشخصات کارفرما و تهیه‌کننده، شناسه رهگیری و جدول نتایج اندازه‌گیری صدا در دو دور', 'گزارش PDF با شناسه رهگیری', 'هر گزارش شناسه و کد QR دارد تا کارفرما اصالتش را در صفحه تأیید فرابهداشت بررسی کند.'],
                ['project-sample.webp', 910, 910, 'نمونه صفحه پروژه اندازه‌گیری: قرائت‌های صدا در پنج ایستگاه و دو دور، با ابزارهای پیشنهادی برای صنعت ریخته‌گری', 'پروژه اندازه‌گیری با دورهای تکراری', 'قرائت هر ایستگاه در هر دور کنار هم ثبت می‌شود و دو دور با هم مقایسه می‌شوند.'],
            ] as [$file, $width, $height, $alt, $title, $caption])
                <figure>
                    {{-- قاب هم‌اندازه تا زیرنویس‌ها هم‌تراز بمانند؛ بالای هر تصویر مهم است. --}}
                    <div class="aspect-[6/5] overflow-hidden rounded-lg border border-line bg-surface">
                        <img src="{{ asset('images/pro/'.$file) }}" alt="{{ $alt }}"
                             width="{{ $width }}" height="{{ $height }}" loading="lazy" decoding="async"
                             class="block size-full object-cover object-top">
                    </div>
                    <figcaption class="mt-3">
                        <span class="block text-h4 text-ink">{{ $title }}</span>
                        <span class="mt-1 block text-label text-muted">{{ $caption }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    <div class="mt-12 grid items-start gap-5 lg:grid-cols-[1.3fr_1fr]">
        <x-card title="اشتراک چه چیزی را باز می‌کند">
            <x-data-table class="mt-5" :headers="['امکان', 'رایگان', 'Pro']" caption="مقایسه پلن رایگان و Pro">
                @foreach ($compare as [$label, $freeValue, $proValue])
                    <tr>
                        <td class="font-semibold text-ink">{{ $label }}</td>
                        <td>
                            @if ($freeValue === null)
                                <span class="text-muted"><x-icon name="close" :size="16" /></span><span class="sr-only">ندارد</span>
                            @else
                                {{ $freeValue }}
                            @endif
                        </td>
                        <td class="font-semibold text-ink">{{ $proValue }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        </x-card>

        <div class="flex flex-col gap-5">
            @if ($isCurrent && $subscription->cancelled_at === null)
                <x-card title="تمدید خودکار">
                    <p class="mt-2.5 text-copy text-body">اگر نمی‌خواهید پس از این دوره تمدید شود، می‌توانید همین حالا متوقفش کنید.</p>
                    <form method="POST" action="{{ route('monetization.cancel') }}" class="mt-5">
                        @csrf
                        <x-button type="submit" variant="secondary">توقف تمدید خودکار</x-button>
                    </form>
                </x-card>
            @endif

            <x-disclaimer>
                اشتراک حرفه‌ای فقط دسترسی به ابزارهای این سایت است و هیچ گواهی، مدرک رسمی یا
                تأیید حرفه‌ای به همراه ندارد.
            </x-disclaimer>
        </div>
    </div>

</x-layouts.public>
