# فرابهداشت

میزکار فارسی متخصص بهداشت حرفه‌ای و ایمنی کار — برای یادگیری، محاسبه، مستندسازی،
خرید و فروش محتوای تخصصی، مشاوره، یافتن شغل و گرفتن پروژه.

> **وعده محصول:** یاد بگیر، محاسبه کن، مستند بساز، کار پیدا کن و متخصص بگیر.

## راه‌اندازی محلی

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run dev
php artisan serve
```

سلامت برنامه: `GET /_health`

## ساختار مخزن

```
app/
├─ Modules/            ماژول‌ها — هر کدام یک واحد مستقل و قابل ارتقا
│  └─ Health/          ماژول نمونه و مرجع ساختار
├─ Support/            ابزارهای مشترک بدون منطق تجاری
│  └─ Modules/         هسته سیستم ماژولار
├─ Contracts/          رابط‌های بین‌ماژولی (تنها راه گفت‌وگوی ماژول‌ها)
└─ Console/Commands/
config/modules.php     فهرست ماژول‌های فعال
stubs/fbh-module/      قالب ساخت ماژول تازه
docs/                  ADRها، معماری، نقشه راه و تصمیم‌های باز
```

## ساختار یک ماژول

هر ماژول همیشه همین چیدمان را دارد، تا وقتی شش ماه بعد سراغش می‌روید بدانید کجا را باز کنید:

```
app/Modules/<Name>/
├─ README.md              چه می‌کند · وابستگی‌ها · نقاط توسعه · نحوه حذف
├─ Domain/                Model، Enum، ValueObject
├─ Actions/               هر کلاس یک کار
├─ Services/              منطق مشترک چند Action
├─ Http/                  Controller، FormRequest
├─ Filament/              Resource و Page پنل مدیریت
├─ Policies/ · Events/ · Contracts/
├─ Providers/<Name>ServiceProvider.php
├─ routes/web.php · routes/admin.php
├─ database/migrations/
├─ resources/views/
└─ tests/
```

مسیرها، قالب‌ها، ترجمه‌ها، مهاجرت‌ها و پیکربندی هر ماژول **خودکار** بارگذاری می‌شوند؛
`ModuleProvider` این کار را می‌کند و ماژول‌ها کد تکراری ندارند.

## افزودن ماژول تازه

```bash
php artisan fbh:make-module Encyclopedia
```

سپس `'Encyclopedia'` را به `config/modules.php` اضافه کنید. ساخته‌شدن ماژول آن را فعال
نمی‌کند — فعال‌سازی همیشه یک تصمیم صریح است.

## حذف یا غیرفعال‌کردن ماژول

یک خط از `config/modules.php` بردارید. برای حذف کامل، پوشه ماژول را هم پاک کنید.
هیچ جای دیگری از برنامه تغییر نمی‌کند. اگر ماژولی در فهرست باشد ولی پوشه‌اش نباشد،
برنامه با پیام روشن می‌ایستد — رد کردن بی‌صدا ممنوع است.

## کیفیت کد

```bash
composer run check   # Pint + PHPStan + Pest — همان چیزی که CI اجرا می‌کند
```

قواعد کامل در `CLAUDE.md` و برنامه بخش‌به‌بخش در `docs/roadmap/coding-plan.md`.
