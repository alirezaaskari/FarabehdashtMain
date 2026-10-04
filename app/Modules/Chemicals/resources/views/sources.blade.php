@php use App\Modules\Chemicals\Domain\Enums\LimitAuthority; @endphp

<x-layouts.public title="منابع و روش کار بانک مواد"
                  description="عددهای بانک مواد فرابهداشت از کجا آمده‌اند، هر مرجع چه می‌گوید و چگونه بررسی می‌شوند."
                  :canonical="route('chemicals.sources')"
                  active="chemicals">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['خانه', Route::has('home') ? route('home') : '/'],
            ['بانک مواد شیمیایی', route('chemicals.index')],
            ['منابع و روش کار', null],
        ]" />
    </x-slot:breadcrumb>

    <x-page-header art="chemicals-sources" title="منابع و روش کار بانک مواد"
                   lede="هیچ عددی در این بانک حدسی نیست. هر حد مواجهه از متن یک مرجع مشخص رونویسی شده و نام، نسخه، پیوند و تاریخ مراجعه‌اش کنار همان عدد است." />

    <x-page-help topic="chemicals-sources" class="mt-5" />

    <section aria-labelledby="rules-heading" class="mt-10">
        <h2 id="rules-heading" class="text-h2 text-ink">سه قاعده</h2>
        <ul class="mt-4 grid list-none gap-4 p-0 md:grid-cols-3">
            <li>
                <x-card class="h-full">
                    <h3 class="text-h4 text-ink">بی‌منبع منتشر نمی‌شود</h3>
                    <p class="mt-2 text-copy text-body">ماده‌ای که حتی یک حدش منبع با ویرایش یا سال نداشته باشد، اصلاً منتشر نمی‌شود.</p>
                </x-card>
            </li>
            <li>
                <x-card class="h-full">
                    <h3 class="text-h4 text-ink">خودتان ببینید</h3>
                    <p class="mt-2 text-copy text-body">نام مرجع کنار هر عدد پیوند است به همان صفحه‌ای که عدد در آن آمده، با تاریخی که دیده شده.</p>
                </x-card>
            </li>
            <li>
                <x-card class="h-full">
                    <h3 class="text-h4 text-ink">هر تغییر ثبت می‌شود</h3>
                    <p class="mt-2 text-copy text-body">اگر عددی عوض شود، مقدار قبلی و تاریخ تغییر در «تاریخچه تغییرات» همان ماده می‌ماند.</p>
                </x-card>
            </li>
        </ul>
    </section>

    <section aria-labelledby="authorities-heading" class="mt-12">
        <h2 id="authorities-heading" class="text-h2 text-ink">مراجع حد مواجهه</h2>
        <p class="mt-2 text-copy text-muted">
            اکنون @fa($substances) ماده منتشر شده است. مرجع‌ها هم‌وزن نیستند و هرکدام برای کاری ساخته شده‌اند:
        </p>

        <dl class="mt-5 divide-y divide-line border-y border-line">
            <div class="py-5">
                <dt class="text-h4 text-ink">{{ LimitAuthority::IranOel->label() }}
                    <span class="ms-2 text-note font-normal text-muted">@fa($limitsBy['iran_oel']) حد</span></dt>
                <dd class="mt-2 text-copy text-body">
                    حد قانونی در ایران، منتشرشده از مرکز سلامت محیط و کار وزارت بهداشت. در داده اولیه نیامده، چون متن رسمی
                    در دسترس ما نبود و عدد را از منبع دست‌دوم نمی‌آوریم؛ مدیر هر حد را از روی متن رسمی و با ذکر ویرایش می‌افزاید.
                </dd>
            </div>
            <div class="py-5">
                <dt class="text-h4 text-ink"><span dir="ltr" data-numeric>NIOSH REL</span>
                    <span class="ms-2 text-note font-normal text-muted">@fa($limitsBy['niosh']) حد</span></dt>
                <dd class="mt-2 text-copy text-body">
                    حد توصیه‌شده مؤسسه ملی ایمنی و بهداشت شغلی آمریکا (زیر CDC) بر پایه شواهد سلامت. الزام قانونی ندارد، ولی
                    معمولاً به‌روزتر از حد قانونی آمریکاست. عدد IDLH هم از همین مؤسسه است.
                    <a href="https://www.cdc.gov/niosh/npg/" rel="noopener noreferrer external" target="_blank"
                       class="inline-flex min-h-touch items-center font-semibold">NIOSH Pocket Guide</a>
                </dd>
            </div>
            <div class="py-5">
                <dt class="text-h4 text-ink"><span dir="ltr" data-numeric>OSHA PEL</span>
                    <span class="ms-2 text-note font-normal text-muted">@fa($limitsBy['osha']) حد</span></dt>
                <dd class="mt-2 text-copy text-body">
                    حد قانونی آمریکا در مقررات 29 CFR 1910. بیشتر این عددها قدیمی‌اند و از آغاز کم به‌روز شده‌اند، پس گاهی
                    از حد NIOSH سهل‌گیرانه‌ترند. در این بانک از همان راهنمای NIOSH رونویسی شده‌اند.
                    <a href="https://www.osha.gov/laws-regs/regulations/standardnumber/1910/1910.1000" rel="noopener noreferrer external" target="_blank"
                       class="inline-flex min-h-touch items-center font-semibold">جدول‌های OSHA</a>
                </dd>
            </div>
            <div class="py-5">
                <dt class="text-h4 text-ink"><span dir="ltr" data-numeric>ACGIH TLV</span>
                    <span class="ms-2 text-note font-normal text-muted">@fa($limitsBy['acgih']) حد</span></dt>
                <dd class="mt-2 text-copy text-body">
                    مرجع رایج بین‌المللی که بسیاری از حدهای کشورها از آن گرفته شده. متن آن حق نشر دارد؛ پس در داده اولیه
                    نیامده و هر عدد فقط با نام ویرایش مشخص کتاب افزوده می‌شود.
                </dd>
            </div>
        </dl>

        <x-alert tone="info" class="mt-5">
            <span dir="ltr" data-numeric>IDLH</span> حد مواجهه روزانه نیست؛ غلظتی است که خطر فوری برای جان دارد و برای انتخاب
            حفاظت تنفسی و برنامه فرار به کار می‌رود. برای همین در جدول ماده برچسب «غیرقابل مقایسه با اندازه‌گیری معمول» دارد.
        </x-alert>
    </section>

    <section aria-labelledby="other-heading" class="mt-12">
        <h2 id="other-heading" class="text-h2 text-ink">منابع بقیه صفحه ماده</h2>
        <ul class="mt-4 flex list-none flex-col gap-3 p-0 text-copy text-body">
            <li>
                <a href="https://www.cdc.gov/niosh/npg/" rel="noopener noreferrer external" target="_blank"
                   class="inline-flex min-h-touch items-center font-semibold">NIOSH Pocket Guide</a>:
                شکل ظاهری، راه‌های ورود، علائم، اندام هدف و حفاظت فردی و تنفسی.
            </li>
            <li>
                <a href="https://www.cdc.gov/niosh/nmam/" rel="noopener noreferrer external" target="_blank"
                   class="inline-flex min-h-touch items-center font-semibold">NIOSH NMAM</a>:
                روش نمونه‌برداری و تجزیه، با شماره روش.
            </li>
            <li>
                <a href="https://pubchem.ncbi.nlm.nih.gov/" rel="noopener noreferrer external" target="_blank"
                   class="inline-flex min-h-touch items-center font-semibold">PubChem</a>:
                جرم مولکولی، که با فرمول هر ماده دوباره سنجیده شده است.
            </li>
            <li>
                <a href="https://monographs.iarc.who.int/list-of-classifications/" rel="noopener noreferrer external" target="_blank"
                   class="inline-flex min-h-touch items-center font-semibold">فهرست طبقه‌بندی IARC</a>:
                گروه سرطان‌زایی ماده.
            </li>
        </ul>
        <p class="mt-4 text-note text-muted">نشانی دقیق هر منبع در پایین صفحه همان ماده، زیر «منابع این صفحه» آمده است.</p>
    </section>

    <x-disclaimer class="mt-12">
        این بانک مرجع آموزشی است و تشخیص پزشکی، تأیید ایمنی یا انطباق قانونی نمی‌دهد. اگر عددی با متن مرجع فرق داشت،
        متن مرجع معتبر است؛ پیش از هر تصمیم عملیاتی به آخرین ویرایش مرجع و SDS سازنده استناد کنید.
    </x-disclaimer>

</x-layouts.public>
