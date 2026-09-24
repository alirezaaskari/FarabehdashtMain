@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="کیف پول"
                     heading="کیف پول"
                     lede="موجودی و گردش کیف پول شما. هر ریال این صفحه از دفتر کل می‌آید و بدون تراکنش ثبت‌شده تغییر نمی‌کند."
                     nav="wallet">

    <div class="grid gap-6 md:grid-cols-2">
        <x-stat label="موجودی فعلی" :value="$balance->format()" tone="primary" :numeric="false" />

        <x-card title="شارژ کیف پول" tone="muted">
            <p class="text-copy text-muted">
                در نسخه فعلی، کیف پول فقط از راه پشتیبانی شارژ می‌شود. رسید واریز را برای
                پشتیبانی بفرستید تا مبلغ پس از بررسی به کیف پول شما اضافه شود.
            </p>
        </x-card>
    </div>

    <div class="mt-8">
        @if ($statement->lines === [])
            <x-empty-state icon="wallet"
                           title="هنوز تراکنشی ندارید"
                           description="شارژ کیف پول، پرداخت با آن و بازگشت وجه خریدها این‌جا ثبت می‌شود." />
        @else
            <x-data-table :headers="['تاریخ', 'شرح', 'مبلغ', 'شناسه']" caption="گردش کیف پول، تازه‌ترین اول">
                @foreach ($statement->lines as $line)
                    <tr>
                        <td>{{ JalaliDate::short($line->occurredAt) }}</td>
                        <td class="font-semibold text-ink">{{ $line->description }}</td>
                        <td @class(['font-bold', 'text-primary' => $line->isCredit(), 'text-danger' => ! $line->isCredit()])>
                            {{ $line->isCredit() ? '+' : '−' }}{{ $line->amount->format() }}
                        </td>
                        <td><span dir="ltr" data-numeric class="text-note text-muted">{{ \Illuminate\Support\Str::limit($line->reference, 8, '') }}</span></td>
                    </tr>
                @endforeach
            </x-data-table>

            @if ($statement->lastPage() > 1)
                <nav aria-label="صفحه‌بندی گردش کیف پول" class="mt-6 flex items-center justify-between gap-4">
                    @if ($statement->page > 1)
                        <x-button :href="route('workspace.wallet', ['page' => $statement->page - 1])" variant="secondary" size="sm">صفحه قبل</x-button>
                    @else
                        <span></span>
                    @endif

                    <span class="text-note text-muted">صفحه @fa($statement->page) از @fa($statement->lastPage())</span>

                    @if ($statement->page < $statement->lastPage())
                        <x-button :href="route('workspace.wallet', ['page' => $statement->page + 1])" variant="secondary" size="sm">صفحه بعد</x-button>
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        @endif
    </div>

</x-layouts.workspace>
