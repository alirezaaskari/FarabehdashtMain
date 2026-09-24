@php
    use App\Modules\Reports\Domain\Enums\ReportStatus;
    use App\Support\JalaliDate;

    $reports = $this->reports();
@endphp

<x-filament-panels::page>

    <x-filament::section>
        <label for="search" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">جست‌وجو با شناسه رهگیری یا عنوان</label>
        <x-filament::input.wrapper>
            <x-filament::input id="search" type="text" wire:model.live.debounce.400ms="search" placeholder="FBH-XXXX-XXXX" />
        </x-filament::input.wrapper>

        @if ($error)
            <p class="mt-3 text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="تازه‌ترین گزارش‌ها">
        @if ($reports->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">گزارشی پیدا نشد.</p>
        @else
            <div class="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($reports as $report)
                    <div class="flex flex-col gap-3 py-4 md:flex-row md:items-center md:justify-between" wire:key="report-{{ $report->id }}">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-950 dark:text-white">{{ $report->title }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <span dir="ltr">{{ $report->tracking_code }}</span>
                                · {{ $report->status->label() }}
                                · {{ JalaliDate::short($report->issued_at ?? $report->created_at) }}
                                · کاربر #{{ $report->user_id }}
                            </p>
                        </div>

                        @if ($report->status !== ReportStatus::Revoked)
                            <div class="flex gap-2">
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model="reasons.{{ $report->id }}" placeholder="دلیل ابطال" />
                                </x-filament::input.wrapper>
                                <x-filament::button color="danger" wire:click="revoke({{ $report->id }})"
                                                    wire:loading.attr="disabled" wire:target="revoke({{ $report->id }})">
                                    ابطال
                                </x-filament::button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

</x-filament-panels::page>
