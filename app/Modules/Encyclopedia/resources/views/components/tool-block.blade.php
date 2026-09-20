@props(['tool'])

{{--
    بلوک ابزار درون متن.

    جای «بروید بخش ابزارها و پیدایش کنید» را می‌گیرد: خواننده همان‌جا که
    مفهوم را خواند، ابزارش را هم می‌بیند. منبع و نسخه فرمول کنار نامش می‌آیند
    تا کاربر بداند چه چیزی را باز می‌کند.

    این بلوک فقط وقتی رندر می‌شود که ماژول ابزارها واقعاً همان ابزار را داشته
    باشد؛ وگرنه کنترلر آن را null برمی‌گرداند و متن بدون بلوک ادامه می‌یابد.
--}}

<div class="my-6 flex flex-col gap-4 rounded-xl border border-primary-line bg-primary-soft px-6 py-5.5
            sm:flex-row sm:items-center">
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary text-on-primary">
        <x-icon name="calculator" :size="22" />
    </span>

    <div class="min-w-0 grow">
        <span class="block text-note font-bold text-on-primary-soft">همین‌جا محاسبه کن</span>
        <span class="mt-1 block text-h4 text-ink">{{ $tool->title }}</span>
        <span class="mt-1.5 block text-note text-muted">
            {{ $tool->summary }}
            —
            <span dir="ltr" data-numeric>{{ $tool->reference }}</span>
            · نسخه فرمول <span dir="ltr" data-numeric>{{ $tool->version }}</span>
        </span>
    </div>

    <x-button :href="route('tools.show', $tool->slug)" variant="primary" class="shrink-0">
        باز کردن ابزار
    </x-button>
</div>
