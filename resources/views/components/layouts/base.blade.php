@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'noindex' => false,
    'theme' => null,
    'bodyClass' => '',
])

{{--
    چیدمان پایه همه صفحات.

    theme = 'light'  → صفحه همیشه روشن می‌ماند (صفحات عمومی سئویی)
    theme = null     → حالت تاریک خودکار و قابل انتخاب کاربر (میزکار و ابزارها)

    فیلدهای سئو اینجا فقط جا باز می‌کنند؛ لایه کامل سئو در بخش ۴ ساخته می‌شود.
--}}

<!DOCTYPE html>
<html lang="fa" dir="rtl" @if ($theme) data-theme="{{ $theme }}" data-lock-theme="true" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    @if ($canonical)
        <link rel="canonical" href="{{ $canonical }}">
    @endif

    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>
<body class="min-h-screen bg-ground text-ink {{ $bodyClass }}">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-3 focus:inline-flex
              focus:h-touch focus:items-center focus:rounded-md focus:bg-primary focus:px-4
              focus:text-sm focus:font-bold focus:text-on-primary">
        رفتن به محتوای اصلی
    </a>

    {{ $slot }}
</body>
</html>
