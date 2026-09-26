<x-layouts.public title="بدون اینترنت"
                  description="ابزارهایی که پیش‌تر باز کرده‌اید بدون اینترنت هم حساب می‌کنند."
                  :noindex="true"
                  active="tools">

    <x-page-header title="اینترنت وصل نیست"
                   lede="این صفحه روی دستگاه شما نگه داشته نشده بود. ابزارهای محاسبه‌ای که پیش‌تر با اینترنت باز کرده‌اید، بدون اینترنت هم باز می‌شوند و حساب می‌کنند." />

    <x-page-help topic="offline" class="mt-5" />

    <div class="mt-8 flex flex-wrap gap-3">
        <x-button :href="route('tools.index')" variant="primary" icon="calculator">رفتن به ابزارها</x-button>
    </div>

</x-layouts.public>
