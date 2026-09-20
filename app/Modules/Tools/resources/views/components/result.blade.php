@props(['rows', 'formula', 'notes' => [], 'disclaimers' => []])

{{--
    نتیجه محاسبه.
    سه چیز همیشه با هم می‌آیند و هیچ‌کدام اختیاری نیست: عدد، نسخه رابطه‌ای که
    آن را ساخته، و سلب ادعا.
--}}

@php
    $reference = $formula->reference();
@endphp

<div class="flex flex-col gap-6">

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($rows as $row)
            <x-stat :label="$row->label"
                    :value="$row->value"
                    :unit="$row->unit"
                    :tone="$loop->first ? 'primary' : 'surface'" />
        @endforeach
    </div>

    @foreach ($notes as $note)
        <x-alert tone="caution">{{ $note }}</x-alert>
    @endforeach

    <x-card title="رابطه، منبع و نسخه" :level="3">
        <dl class="flex flex-col gap-3 text-sm">
            <div class="flex flex-col gap-1">
                <dt class="font-bold text-ink">رابطه</dt>
                <dd class="text-muted" dir="ltr" data-numeric>{{ $reference->relation }}</dd>
            </div>

            <div class="flex flex-col gap-1">
                <dt class="font-bold text-ink">منبع</dt>
                <dd class="text-muted">
                    <span dir="ltr" data-numeric>{{ $reference->title }}</span>
                    — {{ $reference->publisher }}، @fa($reference->year)
                </dd>
            </div>

            @if ($reference->note !== '')
                <div class="flex flex-col gap-1">
                    <dt class="font-bold text-ink">یادداشت منبع</dt>
                    <dd class="text-muted">{{ $reference->note }}</dd>
                </div>
            @endif

            <div class="flex flex-col gap-1">
                <dt class="font-bold text-ink">نسخه رابطه</dt>
                <dd class="text-muted" dir="ltr" data-numeric>
                    {{ $formula->id().'@'.$formula->version() }}
                </dd>
            </div>
        </dl>
    </x-card>

    <section aria-labelledby="limits-heading" class="flex flex-col gap-2">
        <h3 id="limits-heading" class="text-sm font-extrabold text-ink">این عدد چه چیزی نمی‌گوید</h3>

        @foreach ($disclaimers as $line)
            <x-disclaimer size="sm">{{ $line }}</x-disclaimer>
        @endforeach
    </section>

</div>
