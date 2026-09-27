{{-- اعتبار درخواست تماس کارفرما و خرید بسته (DEC-72). --}}
<x-card title="اعتبار درخواست تماس" heading="text-h4">
    @if (! $charging)
        <p class="text-copy text-body">درخواست تماس فعلاً رایگان است.</p>
    @else
        <p class="text-stat text-ink">@fa($credits)</p>
        <p class="text-note text-muted">درخواست باقی‌مانده. فقط درخواست پذیرفته‌شده خرج می‌شود؛ رد یا @fa($replyDays) روز بی‌پاسخی اعتبار را برمی‌گرداند.</p>

        <form method="POST" action="{{ route('jobs.talent.buy') }}" class="mt-5 border-t border-line pt-5">
            @csrf
            <p class="text-label font-semibold text-ink">بسته @fa($packageSize) درخواست · {{ $price->format() }}</p>
            @error('payment')
                <p class="mt-2 text-note font-semibold text-danger" role="alert">{{ $message }}</p>
            @enderror
            <x-payment-method :total="$price" class="my-4" />
            <x-button type="submit" variant="primary" block>خرید بسته</x-button>
        </form>
    @endif
</x-card>
