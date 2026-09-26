<x-layouts.public title="تأیید اصالت گزارش"
                  description="شناسه رهگیری گزارش فرابهداشت را وارد کنید تا ببینید سند بدون تغییر از فرابهداشت صادر شده است."
                  noindex>

    <x-page-header art="reports-verify-form" title="تأیید اصالت گزارش"
                   lede="شناسه رهگیری در پانویس هر صفحه گزارش چاپ شده است؛ یا کد QR روی گزارش را اسکن کنید." />

    <x-card class="mt-8" size="lg">
        <form method="GET" action="{{ route('reports.verify.form') }}" class="flex flex-col gap-4 md:max-w-xl">
            <x-field name="code" label="شناسه رهگیری" :value="$input" required dir="ltr" numeric
                     placeholder="FBH-XXXX-XXXX" autocomplete="off"
                     :error="$invalid ? 'این شناسه شکل درستی ندارد. شناسه با FBH شروع می‌شود و پس از آن هشت حرف و رقم لاتین می‌آید.' : null" />
            <div>
                <x-button type="submit" variant="primary" icon="search">بررسی</x-button>
            </div>
        </form>
    </x-card>

    <x-disclaimer class="mt-8">
        این صفحه فقط تأیید می‌کند که یک سند بدون تغییر از فرابهداشت صادر شده است؛ درستی اندازه‌گیری‌ها و
        تفسیر آن‌ها بر عهده تهیه‌کننده گزارش است.
    </x-disclaimer>

</x-layouts.public>
