@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="تسویه"
                     heading="تسویه"
                     lede="سهم شما از فروش فایل و دوره این‌جا جمع می‌شود. شماره شبا را یک بار ثبت کنید و هر وقت مانده به حد نصاب رسید، درخواست واریز بدهید."
                     nav="vendor-products" help="settlement">

    @if (session('status'))
        <x-alert tone="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @error('payout')
        <x-alert tone="error" class="mb-6">{{ $message }}</x-alert>
    @enderror

    <div class="grid gap-6 md:grid-cols-2">
        <x-stat label="مانده قابل‌تسویه" :value="$owed->format()" tone="primary" :numeric="false" />

        <x-card title="قاعده تسویه" tone="muted">
            <p class="text-copy text-muted">
                کمترین مبلغ هر درخواست {{ $minimum->format() }} است. مدیر مالی هفته‌ای یک بار درخواست‌ها را
                به حساب ثبت‌شده واریز می‌کند و شماره پیگیری بانک را در اعلان‌هایتان می‌بینید.
            </p>
        </x-card>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <x-card title="حساب مقصد">
            @if ($sheba)
                <p class="text-copy text-muted">
                    واریز به حساب <span dir="ltr" data-numeric class="font-semibold text-ink">{{ $sheba->masked() }}</span>
                    به نام <span class="font-semibold text-ink">{{ $account->holder_name }}</span>.
                </p>
            @else
                <p class="text-copy text-muted">هنوز حسابی ثبت نکرده‌اید. شبا باید به نام خود شما باشد.</p>
            @endif

            <form method="POST" action="{{ route('commerce.vendor.settlement.account') }}" class="mt-6 flex flex-col gap-5">
                @csrf
                @method('PUT')

                <x-field name="sheba" label="{{ $sheba ? 'شماره شبای تازه' : 'شماره شبا' }}" required numeric inputmode="text"
                         autocomplete="off" placeholder="IR000000000000000000000000"
                         :value="old('sheba')" :error="$errors->first('sheba')"
                         hint="«IR» و ۲۴ رقم؛ از اپ بانک یا پشت کارت کپی کنید. شبا رمزنگاری‌شده نگه‌داری می‌شود." />

                <x-field name="holder_name" label="نام صاحب حساب" required
                         :value="old('holder_name', $account?->holder_name)" :error="$errors->first('holder_name')" />

                <div>
                    <x-button type="submit" variant="secondary" icon="check">{{ $sheba ? 'عوض کردن حساب' : 'ثبت حساب' }}</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="درخواست واریز">
            @if ($open)
                <p class="text-copy text-muted">
                    درخواست {{ $open->amount()->format() }} از {{ JalaliDate::long($open->created_at) }} در انتظار واریز است.
                    تا واریز یا لغو آن، درخواست تازه‌ای پذیرفته نمی‌شود.
                </p>

                <form method="POST" action="{{ route('commerce.vendor.settlement.cancel', $open->uuid) }}" class="mt-6">
                    @csrf
                    <x-button type="submit" variant="danger" icon="close">لغو درخواست</x-button>
                </form>
            @elseif ($canRequest)
                <form method="POST" action="{{ route('commerce.vendor.settlement.request') }}" class="flex flex-col gap-5">
                    @csrf

                    <x-field name="amount" label="مبلغ (تومان)" required numeric inputmode="numeric"
                             :value="old('amount', $owed->toman)" :error="$errors->first('amount')"
                             hint="هر مبلغی از کمترین مبلغ تا کل مانده." />

                    <div>
                        <x-button type="submit" icon="wallet">درخواست واریز</x-button>
                    </div>
                </form>
            @elseif (! $account)
                <p class="text-copy text-muted">برای درخواست واریز، اول حساب مقصد را ثبت کنید.</p>
            @else
                <p class="text-copy text-muted">
                    مانده شما هنوز به {{ $minimum->format() }} نرسیده است. با فروش بیشتر، دکمه درخواست همین‌جا باز می‌شود.
                </p>
            @endif
        </x-card>
    </div>

    <div class="mt-8">
        @if ($history->isEmpty())
            <x-empty-state icon="wallet"
                           title="هنوز درخواست تسویه‌ای نداده‌اید"
                           description="هر درخواست و نتیجه‌اش، با شماره پیگیری بانک، این‌جا می‌ماند." />
        @else
            <x-data-table :headers="['تاریخ', 'مبلغ', 'وضعیت', 'توضیح']" caption="درخواست‌های تسویه، تازه‌ترین اول">
                @foreach ($history as $payout)
                    <tr>
                        <td>{{ JalaliDate::short($payout->created_at) }}</td>
                        <td class="font-semibold text-ink">{{ $payout->amount()->format() }}</td>
                        <td><x-badge :tone="$payout->status->tone()">{{ $payout->status->label() }}</x-badge></td>
                        <td class="text-note text-muted">
                            @if ($payout->bank_reference)
                                پیگیری <span dir="ltr" data-numeric>{{ $payout->bank_reference }}</span>
                            @else
                                {{ $payout->note }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </div>

</x-layouts.workspace>
