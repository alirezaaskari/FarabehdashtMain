{{--
    صفحه مرجع سیستم طراحی.

    بازتولید واقعی آرت‌بورد «سیستم طراحی» پروتوتایپ با کامپوننت‌های زنده.
    این صفحه فقط خارج از محیط production در دسترس است و ایندکس نمی‌شود.
--}}

<x-layouts.workspace title="سیستم طراحی"
                     heading="سیستم طراحی فرابهداشت"
                     lede="پایه بصری مشترک همه صفحات: رنگ، تایپوگرافی، کنترل‌ها و شش حالت اجباری هر
                           کامپوننت. هر مقدار اینجا یک توکن است؛ تغییرش در یک نقطه انجام می‌شود.">

    {{-- ─────────────── پالت رنگ ─────────────── --}}
    <h2 class="text-h2 text-ink">پالت رنگ</h2>
    <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-6">
        @foreach ([
            ['Primary', 'bg-primary', '--fbh-primary', 'دکمه اصلی، لینک، تأکید'],
            ['Primary Deep', 'bg-primary-deep', '--fbh-primary-deep', 'حالت Hover و سطح تیره'],
            ['Primary Soft', 'bg-primary-soft', '--fbh-primary-soft', 'نشان، نقل قول، پس‌زمینه ملایم'],
            ['Ink', 'bg-ink', '--fbh-ink', 'متن اصلی، فوتر'],
            ['Caution', 'bg-caution', '--fbh-caution', 'هشدار و سلب مسئولیت'],
            ['Danger', 'bg-danger', '--fbh-danger', 'خطا و عملیات برگشت‌ناپذیر'],
            ['Ground', 'bg-ground', '--fbh-ground', 'پس‌زمینه صفحه'],
            ['Surface', 'bg-surface', '--fbh-surface', 'کارت و پنل'],
            ['Line', 'bg-line', '--fbh-border', 'خط جداکننده و قاب'],
            ['Chart ۱', 'bg-chart-1', '--fbh-chart-1', 'دور اول نمودار مقایسه'],
            ['Chart ۲', 'bg-chart-2', '--fbh-chart-2', 'دور دوم نمودار مقایسه'],
            ['Focus', 'bg-focus', '--fbh-focus', 'حلقه فوکوس کیبورد'],
        ] as [$name, $bg, $token, $use])
            <div class="overflow-hidden rounded-lg border border-line bg-surface">
                <div class="h-22 border-b border-line {{ $bg }}"></div>
                <div class="p-3.5">
                    <span class="block text-label font-bold text-ink">{{ $name }}</span>
                    <span class="mt-1 block text-note text-muted" data-numeric>{{ $token }}</span>
                    <span class="mt-1.5 block text-note text-muted">{{ $use }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ─────────────── تایپوگرافی و کنترل‌ها ─────────────── --}}
    <div class="mt-11 grid gap-6 lg:grid-cols-[1.2fr_1fr]">
        <x-card title="تایپوگرافی — Vazirmatn"
                subtitle="یک خانواده، با کنتراست وزن به‌جای کنتراست فونت. ارقام فارسی در متن، ارقام لاتین در خروجی عددی و واحد.">
            <p class="text-display text-ink">عنوان قهرمان ۴۰ / ۸۰۰ — text-display</p>
            <p class="mt-3.5 text-h1 text-ink">عنوان صفحه ۳۴ / ۸۰۰ — text-h1</p>
            <p class="mt-3.5 text-h2 text-ink">عنوان بخش ۲۲ / ۸۰۰ — text-h2</p>
            <p class="mt-3.5 text-h3 text-ink">عنوان کارت ۱۹ / ۸۰۰ — text-h3</p>
            <p class="mt-3.5 text-h4 text-ink">عنوان زیربخش ۱۶ / ۷۰۰ — text-h4</p>
            <p class="mt-3.5 text-lede text-muted">
                بند معرفی ۱۷ با ارتفاع خط ۲٫۱ — text-lede. زیر هر عنوان صفحه می‌آید.
            </p>
            <p class="mt-3.5 text-copy text-body">
                متن بدنه ۱۵ — text-copy. برای متن فارسی طولانی، فاصله خط سخاوتمندانه‌تر از لاتین
                لازم است تا خوانایی حفظ شود.
            </p>
            <p class="mt-3.5 text-note text-muted">
                توضیح کمکی ۱۳ — text-note، کمترین اندازه مجاز روی پس‌زمینه روشن.
            </p>
            <p class="mt-3.5 text-copy text-ink" data-numeric>26.8 °C · 87.5 dB · 108-88-3</p>
        </x-card>

        <x-card title="کنترل‌ها" subtitle="هدف لمسی هیچ‌گاه کمتر از ۴۴ پیکسل نیست.">
            <div class="flex flex-col gap-3">
                <x-button variant="primary" block>دکمه اصلی</x-button>
                <x-button variant="secondary" block>دکمه ثانویه</x-button>
                <x-button variant="ghost" block>دکمه متنی</x-button>
                <x-button variant="danger" block>عملیات خطرناک</x-button>
                <x-button variant="primary" block disabled>دکمه غیرفعال</x-button>
            </div>

            <div class="mt-6 flex flex-col gap-4">
                <x-field name="ds_ok" label="فیلد عادی" placeholder="متن نمونه" hint="راهنمای کوتاه زیر فیلد." />
                <x-field name="ds_num" label="فیلد عددی با واحد" value="24.5" suffix="°C" numeric />
                <x-field name="ds_err" label="فیلد دارای خطا" value="۱۲a"
                         error="مقدار باید عددی و بزرگ‌تر از صفر باشد." />
            </div>

            <div class="mt-6 flex flex-col gap-4 border-t border-line pt-5">
                <x-toggle name="ds_toggle_on" label="حضور در بانک رزومه"
                          description="پیش‌فرض خاموش است و نیازمند اجازه صریح شماست." />
                <x-toggle name="ds_toggle_off" label="اعلان پیامکی" :checked="true" />
            </div>
        </x-card>
    </div>

    {{-- ─────────────── شش حالت اجباری ─────────────── --}}
    <h2 class="mt-11 text-h2 text-ink">شش حالت اجباری هر کامپوننت</h2>
    <p class="mt-2 text-label text-muted">
        هیچ کامپوننتی بدون تعریف این شش حالت تحویل نمی‌شود. مثال زیر روی کارت «محاسبات ذخیره‌شده» میزکار.
    </p>

    <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-lg border border-line bg-surface p-6">
            <span class="text-note font-bold text-muted">Loading</span>
            <div class="mt-4"><x-skeleton :lines="3" /></div>
            <p class="mt-4 text-note text-muted">اسکلت محتوا، نه چرخنده وسط صفحه.</p>
        </div>

        <x-empty-state icon="calculator"
                       title="هنوز محاسبه‌ای ذخیره نکرده‌اید"
                       description="نتیجه هر ابزار را می‌توانید در میزکار نگه دارید و بعداً در گزارش استفاده کنید.">
            <x-slot:action>
                <x-button href="#" variant="secondary" size="sm">رفتن به ابزارها</x-button>
            </x-slot:action>
        </x-empty-state>

        <x-alert tone="error" title="فهرست محاسبات بارگذاری نشد">
            ارتباط با سرور برقرار نشد. اتصال اینترنت را بررسی کنید و دوباره تلاش کنید.
        </x-alert>

        <x-alert tone="success" title="ذخیره شد">
            محاسبه «WBGT — سالن ریخته‌گری» در میزکار ذخیره شد.
        </x-alert>

        <div class="rounded-lg border border-line bg-surface p-6">
            <span class="text-note font-bold text-muted">Disabled</span>
            <p class="mt-3 text-label text-disabled-ink">برای ذخیره‌کردن باید وارد حساب خود شوید.</p>
            <div class="mt-4"><x-button variant="primary" size="sm" disabled>ذخیره در میزکار</x-button></div>
        </div>

        <x-permission-notice title="این بخش برای پروفایل فروشنده است"
                             description="پروفایل فروشندگی شما هنوز تأیید نشده است.">
            <x-slot:action>
                <x-button href="#" variant="secondary" size="sm">مشاهده وضعیت درخواست</x-button>
            </x-slot:action>
        </x-permission-notice>
    </div>

    {{-- ─────────────── نشان، آمار و سلب مسئولیت ─────────────── --}}
    <h2 class="mt-11 text-h2 text-ink">نشان‌ها و آمار</h2>
    <div class="mt-5 flex flex-wrap gap-2.5">
        <x-badge tone="primary" icon="check">تأییدشده</x-badge>
        <x-badge tone="caution" icon="alert">در انتظار بررسی مدیر</x-badge>
        <x-badge tone="danger" icon="close">ردشده</x-badge>
        <x-badge tone="neutral">پیش‌نویس</x-badge>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-stat label="موجودی کیف پول" value="۰" unit="تومان" />
        <x-stat label="محاسبات ذخیره‌شده" value="۵" note="سقف پلن رایگان" />
        <x-stat label="میانگین کاهش تراز" value="−6.0" unit="dB" tone="primary" />
        <x-stat label="ابزارهای فعال" value="۱۰" note="از ۲۰" />
    </div>

    <div class="mt-7">
        <x-disclaimer>
            خروجی ابزارها و محتوای فرابهداشت جنبه آموزشی و کمک‌کارشناسی دارد و ادعای تشخیص پزشکی،
            تأیید ایمنی قطعی یا انطباق قانونی قطعی ندارد. گواهی و نشان‌های داخلی سایت مدرک رسمی یا
            مجوز حرفه‌ای محسوب نمی‌شوند.
        </x-disclaimer>
    </div>
</x-layouts.workspace>
