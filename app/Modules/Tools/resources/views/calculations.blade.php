@php use App\Support\JalaliDate; @endphp

<x-layouts.workspace title="محاسبات ذخیره‌شده"
                     heading="محاسبات ذخیره‌شده"
                     lede="هر محاسبه با ورودی‌ها و نسخه فرمول لحظه ثبت نگهداری می‌شود و قابل بازتولید است."
                     active="tools"
                     nav="calculations" help="calculations">

    <x-slot:actions>
        <x-button :href="route('tools.index')" variant="primary" icon="calculator">محاسبه تازه</x-button>
    </x-slot:actions>

    @if (session('status'))
        <div class="mb-6"><x-alert tone="success">{{ session('status') }}</x-alert></div>
    @endif

    @if ($calculations->isEmpty())
        <x-empty-state icon="calculator"
                       title="هنوز محاسبه‌ای ذخیره نکرده‌اید"
                       description="نتیجه هر ابزار را می‌توانید با یک نام در میزکار نگه دارید.">
            <x-slot:action>
                <x-button :href="route('tools.index')" variant="primary" size="sm">رفتن به ابزارها</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <x-data-table :headers="['عنوان محاسبه', 'ابزار', 'نسخه فرمول', 'تاریخ', 'اقدام']"
                      caption="فهرست محاسبات ذخیره‌شده">
            @foreach ($calculations as $item)
                <tr>
                    <td class="font-semibold text-ink">{{ $item->label ?? $item->tool_slug }}</td>
                    <td><span dir="ltr" data-numeric>{{ $item->tool_slug }}</span></td>
                    <td><span dir="ltr" data-numeric>{{ $item->formula_version }}</span></td>
                    <td>{{ JalaliDate::short($item->created_at) }}</td>
                    <td>
                        <a href="{{ route('tools.calculations.show', $item->uuid) }}"
                           class="inline-flex min-h-touch items-center font-semibold">باز کردن</a>
                    </td>
                </tr>
            @endforeach

            <x-slot:footnote>
                محاسبه ذخیره‌شده تغییرناپذیر است؛ ویرایش یک محاسبه، رکورد جدید می‌سازد. حذف از صفحه خود محاسبه است.
            </x-slot:footnote>
        </x-data-table>

        <div class="mt-6">{{ $calculations->links() }}</div>
    @endif

</x-layouts.workspace>
