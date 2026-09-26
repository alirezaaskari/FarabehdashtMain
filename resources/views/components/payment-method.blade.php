{{--
    روش پرداخت. رادیو واقعی است تا با کیبورد و صفحه‌خوان کار کند.
    کیف پول ناکافی غیرفعال می‌ماند و درگاه پیش‌فرض است.
    inverse برای ستون برجسته روی پس‌زمینه سبز.
--}}
@php
    $muted = $inverse ? 'text-primary-soft' : 'text-muted';
    $body = $inverse ? '' : 'text-body';
    $accent = $inverse ? 'accent-surface' : 'accent-primary';
@endphp

<fieldset {{ $attributes->merge(['class' => 'flex flex-col gap-1']) }}>
    <legend class="mb-1 text-note font-semibold {{ $muted }}">روش پرداخت</legend>

    <label class="flex min-h-touch cursor-pointer items-center gap-2.5 text-label {{ $body }}">
        <input type="radio" name="payment" value="{{ $gateway->value }}" checked
               class="size-5 shrink-0 {{ $accent }}">
        {{ $gateway->label() }}
    </label>

    <label @class([
        'flex min-h-touch items-center gap-2.5 text-label',
        'cursor-pointer '.$body => $walletCoversTotal,
        'cursor-not-allowed '.$muted => ! $walletCoversTotal,
    ])>
        <input type="radio" name="payment" value="{{ $wallet->value }}"
               @disabled(! $walletCoversTotal)
               aria-describedby="{{ $balanceId }}"
               class="size-5 shrink-0 {{ $accent }}">
        {{ $wallet->label() }}
        <span id="{{ $balanceId }}" class="text-note {{ $muted }}">
            (موجودی: <span dir="ltr" data-numeric>{{ $balance->formatWithoutUnit() }}</span> تومان{{ $walletCoversTotal ? '' : '، کافی نیست' }})
        </span>
    </label>

    @error('payment')
        <p @class(['text-note', 'text-danger' => ! $inverse]) role="alert">{{ $message }}</p>
    @enderror
</fieldset>
