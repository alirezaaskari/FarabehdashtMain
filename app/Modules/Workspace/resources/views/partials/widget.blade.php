{{--
    یک کارت میزکار. شکل برای همه ماژول‌ها یکی است: آمار، فهرست، حالت خالی و
    پیوند ادامه. قالب نام هیچ ماژولی را نمی‌داند.
--}}

<x-card :title="$widget->title" class="flex flex-col">
    @if ($widget->stats !== [])
        <dl class="grid grid-cols-2 gap-4">
            @foreach ($widget->stats as $stat)
                <div>
                    <dt class="text-note font-semibold text-muted">{{ $stat->label }}</dt>
                    <dd class="mt-1 text-stat text-ink">{{ $stat->value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @if ($widget->rows !== [])
        <ul @class(['flex flex-col divide-y divide-line-soft', 'mt-5' => $widget->stats !== []])>
            @foreach ($widget->rows as $row)
                <li class="flex min-h-touch items-center justify-between gap-3 py-2">
                    @if ($row->url)
                        <a href="{{ $row->url }}" class="flex min-h-touch min-w-0 items-center text-label font-semibold">
                            <span class="truncate">{{ $row->label }}</span>
                        </a>
                    @else
                        <span class="min-w-0 truncate text-label font-semibold text-ink">{{ $row->label }}</span>
                    @endif

                    @if ($row->meta)
                        <span class="shrink-0 text-note text-muted">{{ $row->meta }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @elseif ($widget->empty)
        <p @class(['text-copy text-muted', 'mt-5' => $widget->stats !== []])>{{ $widget->empty }}</p>
    @endif

    @if ($widget->actionUrl)
        <div class="mt-5">
            <x-button :href="$widget->actionUrl" variant="secondary" size="sm">{{ $widget->actionLabel ?? 'بیشتر' }}</x-button>
        </div>
    @endif
</x-card>
