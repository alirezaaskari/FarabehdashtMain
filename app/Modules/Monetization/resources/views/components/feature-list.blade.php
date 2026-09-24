@props(['items', 'inverse' => false])

{{--
    فهرست امکانات یک پلن: تیک برای هست، ضربدر کم‌رنگ برای نیست. هر ردیف
    متن «ندارد» برای صفحه‌خوان هم دارد؛ معنا فقط با رنگ و آیکن منتقل نمی‌شود.

    inverse برای ستون برجسته روی پس‌زمینه سبز.
--}}

<ul {{ $attributes->merge(['class' => 'flex list-none flex-col gap-3 ps-0 text-label']) }}>
    @foreach ($items as [$label, $included])
        <li @class([
            'flex items-start gap-2.5',
            'text-body' => $included && ! $inverse,
            'text-muted' => ! $included,
        ])>
            <span @class(['mt-0.5', 'text-primary' => $included && ! $inverse, 'text-primary-soft' => $inverse])>
                <x-icon :name="$included ? 'check' : 'close'" :size="16" :stroke="2.5" />
            </span>
            <span>{{ $label }}@unless ($included)<span class="sr-only"> (ندارد)</span>@endunless</span>
        </li>
    @endforeach
</ul>
