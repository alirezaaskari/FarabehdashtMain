@props(['name'])

{{--
    تصویر نقاشی‌گونه سایت: دو کارشناس همیشگی (آقا و خانم) در صحنه‌ای مخصوص
    همان صفحه، یا یک طبیعت بی‌جان برای حالت خالی.

    هر تصویر یک SVG درون‌خطی است: خط currentColor، کاغذ surface و رنگ‌ها از
    توکن‌های --fbh-art-*؛ هیچ فایل یا سرویس بیرونی بار نمی‌شود. تزئینی است
    (aria-hidden) و معنا همیشه در متن کنارش هست. هر نام فقط یک جای سایت
    به کار می‌رود تا تصویری تکرار نشود؛ نام ناشناخته چیزی نمی‌کشد.

    فایل‌ها را scripts/art/generate.py می‌سازد؛ دستی ویرایش نشوند.
--}}

@if (preg_match('/^[a-z0-9-]+$/', $name) === 1 && view()->exists('components.art.pictures.'.$name))
    @include('components.art.pictures.'.$name, ['attributes' => $attributes])
@endif
