@props(['article', 'freshness', 'daysUntilDue' => null])

{{--
    نشانگر تازگی داده.

    خواننده باید بدون خواندن تاریخ بفهمد این متن چقدر تازه است. وقتی موعد
    بازبینی گذشته باشد، همین نوار **هشدار می‌دهد** و پنهانش نمی‌کند: متن عقب
    که به‌جای تازه معرفی شود، بدتر از نبودنش است.
--}}

@php
    use App\Support\JalaliDate;
    use App\Support\PersianDigits;

    $tone = $freshness->tone();

    $surface = [
        'primary' => 'border-primary-line bg-primary-soft text-on-primary-soft',
        'caution' => 'border-caution-line bg-caution-soft text-caution-ink',
        'danger' => 'border-danger-line bg-danger-soft text-danger-ink',
    ][$tone] ?? 'border-line bg-surface-2 text-body';
@endphp

<div {{ $attributes->merge([
    'class' => 'flex flex-wrap items-center gap-x-4 gap-y-2 rounded-note border px-5 py-3.5 '.$surface,
]) }}>
    <span class="flex items-center gap-2 text-note font-semibold">
        <x-icon :name="$freshness->icon()" :size="17" :stroke="2.2" />
        {{ $freshness->label() }}
    </span>

    @if ($article->reviewed_at)
        <span class="text-note">
            آخرین بازبینی علمی: <strong>{{ JalaliDate::short($article->reviewed_at) }}</strong>
        </span>
    @endif

    @if ($article->review_due_at)
        <span class="text-note">
            @if ($daysUntilDue !== null && $daysUntilDue < 0)
                موعد بازبینی <strong>{{ PersianDigits::from(abs($daysUntilDue)) }} روز</strong> گذشته است
            @else
                بازبینی بعدی: <strong>{{ JalaliDate::short($article->review_due_at) }}</strong>
            @endif
        </span>
    @endif

    <span class="ms-auto">
        <x-button :href="route('encyclopedia.print', $article->slug)" variant="secondary" size="sm" icon="print">
            نسخه چاپی
        </x-button>
    </span>
</div>
