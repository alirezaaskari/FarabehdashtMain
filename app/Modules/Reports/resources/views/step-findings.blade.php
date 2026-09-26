<x-layouts.workspace art="reports-step-findings" :title="'یافته‌ها — '.($report->title ?? 'گزارش')"
                     heading="یافته‌ها و توصیه‌ها"
                     lede="تفسیر نتایج کار شماست؛ گزارش‌ساز هیچ متنی جای کارشناس نمی‌نویسد. هر دو بخش اختیاری‌اند."
                     nav="reports">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['گزارش‌ها', route('reports.index')], [$report->title ?? 'گزارش', null]]" />
    </x-slot:breadcrumb>

    @include('reports::partials.steps', ['current' => $step, 'report' => $report])

    <x-card size="lg">
        <form method="POST" action="{{ route('reports.findings.save', $report->uuid) }}" class="flex flex-col gap-5">
            @csrf
            @method('PUT')

            <div>
                <label for="findings" class="mb-2 block text-label font-semibold text-ink">یافته‌ها</label>
                <textarea id="findings" name="findings" rows="8"
                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('findings', $report->findings) }}</textarea>
                @error('findings')<p class="mt-2 text-note font-semibold text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="recommendations" class="mb-2 block text-label font-semibold text-ink">توصیه‌ها</label>
                <textarea id="recommendations" name="recommendations" rows="6"
                          class="w-full rounded-md border border-line-strong bg-surface px-3.5 py-2.5 text-control text-ink">{{ old('recommendations', $report->recommendations) }}</textarea>
                @error('recommendations')<p class="mt-2 text-note font-semibold text-danger">{{ $message }}</p>@enderror
            </div>

            <div>
                <x-button type="submit" variant="primary" icon="forward">ذخیره و ادامه</x-button>
            </div>
        </form>
    </x-card>

</x-layouts.workspace>
