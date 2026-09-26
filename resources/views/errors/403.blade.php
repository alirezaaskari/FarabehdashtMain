<x-errors.layout
    art="character.key"
    code="403"
    title="اجازه دسترسی ندارید"
    message="این بخش به نقشی نیاز دارد که روی حساب شما فعال نیست.">
    @if (auth()->check() && Route::has('identity.profiles'))
        <x-button :href="route('identity.profiles')" variant="secondary">دیدن نقش‌های من</x-button>
    @endif
</x-errors.layout>
