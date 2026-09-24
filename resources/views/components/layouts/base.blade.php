@props([
    'seo' => null,
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

    سئو دو راه دارد: پارامتر `seo` با یک شیء SeoMeta (راه اصلی برای صفحات
    محتوایی)، یا همان title و description جداگانه برای صفحات ساده. اگر هر دو
    داده شوند، SeoMeta برنده است چون تصمیم‌هایش با هم گرفته شده‌اند.
--}}

@php
    $seoTitle = $seo?->title ?? $title;
    $seoDescription = $seo?->description ?? $description;
    $seoCanonical = $seo?->canonical ?? $canonical;
    $seoNoindex = $seo?->noindex ?? $noindex;
    $seoImage = $seo?->image;
    $seoSchema = $seo?->schema;

    // گوگل عنوان بلندتر از حدود ۶۰ نویسه را می‌بُرد. اگر نام سایت عنوان را از
    // این حد بگذراند، نام سایت می‌رود، نه انتهای عنوان صفحه.
    $siteName = config('app.name');
    $fullTitle = $seoTitle ? $seoTitle.' — '.$siteName : $siteName;
    $documentTitle = $seoTitle && mb_strlen($fullTitle) > 60 ? $seoTitle : $fullTitle;
@endphp

<!DOCTYPE html>
<html lang="fa" dir="rtl" @if ($theme) data-theme="{{ $theme }}" data-lock-theme="true" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $documentTitle }}</title>

    @if ($seoDescription)
        <meta name="description" content="{{ $seoDescription }}">
    @endif

    {{-- صفحه noindex نباید Canonical بدهد؛ دو پیام متناقض به موتور جست‌وجو. --}}
    @if ($seoCanonical && ! $seoNoindex)
        <link rel="canonical" href="{{ $seoCanonical }}">
    @endif

    @if ($seoNoindex)
        <meta name="robots" content="noindex, nofollow">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $seoTitle ?? config('app.name') }}">

    @if ($seoDescription)
        <meta property="og:description" content="{{ $seoDescription }}">
    @endif

    @if ($seoImage)
        <meta property="og:image" content="{{ $seoImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif

    {{-- JSON_HEX_TAG: عنوان دوره و محصول را فروشنده می‌نویسد؛ «</script>» در
         آن نباید بتواند از این تگ بیرون بزند. --}}
    @if ($seoSchema)
        <script type="application/ld+json">{!! json_encode($seoSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>
<body class="min-h-screen bg-ground text-ink {{ $bodyClass }}">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-3 focus:inline-flex
              focus:h-touch focus:items-center focus:rounded-md focus:bg-primary focus:px-4
              focus:text-label focus:font-bold focus:text-on-primary">
        رفتن به محتوای اصلی
    </a>

    {{ $slot }}
</body>
</html>
