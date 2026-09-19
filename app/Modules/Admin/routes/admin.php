<?php

declare(strict_types=1);

use App\Modules\Admin\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

/*
| ModuleProvider این فایل را با پیشوند مسیر `admin/` و پیشوند نام `admin.` بار
| می‌کند. این‌ها مسیرهای پنل نیستند (پنل نشانی غیرقابل‌حدس خودش را دارد)، پس
| قابل‌حدس‌بودنشان اشکالی ندارد: هر دو POST و پشت auth هستند.
|
| شروع مشاهده، توانایی مدیریتی می‌خواهد. پایانش عمداً چنین شرطی ندارد: کسی که
| در این حالت گیر کرده باید همیشه بتواند برگردد، حتی اگر نقشش وسط کار عوض شده
| باشد. خودِ سرویس بررسی می‌کند که مشاهده‌ای فعال باشد.
*/

// مسیر پایان اول می‌آید و پارامتر مسیر شروع هم فقط عدد را می‌پذیرد؛ وگرنه
// `/impersonate/stop` با `{user}` تطبیق می‌خورد و «stop» را شناسه کاربر
// می‌فهمد.
Route::middleware('auth')
    ->post('/impersonate/stop', [ImpersonationController::class, 'stop'])
    ->name('impersonate.stop');

Route::middleware(['auth', 'admin.ability:admin.impersonate'])
    ->post('/impersonate/{user}', [ImpersonationController::class, 'start'])
    ->whereNumber('user')
    ->name('impersonate.start');
