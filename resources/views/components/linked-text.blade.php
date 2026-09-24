@props(['segments'])

{{--
    یک بند با پیوندهای داخلی خودکار. تکه‌ها از موتور پیوند می‌آیند و هر دو
    نوعش escape‌شده چاپ می‌شوند؛ هیچ HTML خامی از متن محتوا عبور نمی‌کند.
    بدون فاصله اضافه بین تکه‌ها، تا متن فارسی درست به هم بچسبد.

    py-2.5 روی پیوند درون‌خطی ارتفاع خط را عوض نمی‌کند ولی ناحیه لمس را به
    ۴۴ پیکسل می‌رساند؛ قاعده هدف لمسی پروژه استثنای «پیوند درون متن» ندارد.
--}}
@foreach ($segments as $segment)@if ($segment->isLink())<a href="{{ $segment->url }}" class="py-2.5 box-decoration-clone font-semibold text-primary underline decoration-primary-line underline-offset-4 hover:decoration-primary">{{ $segment->text }}</a>@else{{ $segment->text }}@endif@endforeach
