@props(['name', 'gender' => null])

{{--
    تصویر خطی سایت: شخصیت کارشناس (آقا یا خانم) در یک حالت، یا یک صحنه.

    name = 'character.<حالت>' یا 'scene.<صحنه>'. هر تصویر یک SVG درون‌خطی است
    با currentColor و کلاس‌های معنایی، پس در حالت تاریک خودش برعکس می‌شود و
    هیچ فایل یا سرویس بیرونی بار نمی‌کند. تزئینی است (aria-hidden)؛ معنا
    همیشه در متن کنارش هست.

    آقا و خانم در صفحه‌ها یکی‌درمیان می‌آیند؛ این جدول برای هر حالت یکی را
    ثابت نگه می‌دارد تا یک صفحه با هر بار باز شدن عوض نشود. نام ناشناخته
    چیزی نمی‌کشد تا صفحه هرگز به‌خاطر تصویر نشکند.

    فایل‌ها را scripts/art/generate.py می‌سازد؛ دستی ویرایش نشوند.
--}}

@php
    $women = ['read', 'report', 'chemical', 'ask', 'calendar', 'verify', 'wave', 'think', 'wallet', 'idcard', 'writer'];

    [$kind, $key] = array_pad(explode('.', $name, 2), 2, '');

    $view = match ($kind) {
        'character' => 'components.art.character.'.$key.'-'.($gender ?? (in_array($key, $women, true) ? 'f' : 'm')),
        'scene' => 'components.art.scene.'.$key,
        default => null,
    };
@endphp

@if ($view !== null && view()->exists($view))
    @include($view, ['attributes' => $attributes])
@endif
