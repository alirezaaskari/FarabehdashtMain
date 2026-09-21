# هسته مالی — `Modules/Ledger`

دفتر کل دوطرفه (Double-Entry) و کیف پول کاربر، طبق `docs/adr/ADR-0003-money-and-ledger.md`.
هر ماژولی که پول جابه‌جا می‌کند (کمیسیون، امانت پروژه، شارژ کیف پول) از این
ماژول عبور می‌کند؛ خودِ Ledger هیچ‌چیزی درباره کمیسیون یا پروژه نمی‌داند.

## قاعده‌ای که این ماژول نگه می‌دارد

> **جمع هر تراکنش صفر است؛ اجرای دوباره با همان کلید اثر دوم ندارد.**

این دو ناوردا در کد سرویس (`Services/LedgerService::record()`) اجرا می‌شود،
نه در Trigger دیتابیس:

1. **جمع صفر:** جمع بستانکار منهای بدهکار همه ردیف‌های یک تراکنش پیش از هر
   نوشتنی بررسی می‌شود؛ نامتوازن بودن، خطای فراخوان است نه چیزی که دفتر کل
   بی‌صدا تحمل کند.
2. **Idempotency:** هر تراکنش یک `idempotency_key` یکتا دارد. اگر کلیدی
   تکراری برسد، نوشتن تازه‌ای انجام نمی‌شود و رسید همان تراکنش قبلی
   برمی‌گردد — امن برای صف‌های کاری که یک Job را دوباره اجرا می‌کنند.

## سه جدول فقط‌افزودنی + یک جدول کش

- `ledger_accounts` — حساب‌ها. یکتا روی (نوع، نوع مالک، شناسه مالک). بعد از
  ساخته‌شدن هیچ‌وقت تغییر نمی‌کند، حتی نوعش؛ حساب اشتباه یعنی حساب تازه.
- `ledger_transactions` — سرصفحه هر عملیات مالی، با `idempotency_key` یکتا.
- `ledger_entries` — ردیف‌های واقعی: هر ردیف یک `direction` (بدهکار/بستانکار)
  و یک `amount_toman` غیرمنفی دارد (قاعده `Money`: مبلغ منفی وجود ندارد،
  جهت با Enum بیان می‌شود نه علامت عدد).
- `wallets` — تنها جدولی که *تغییر* می‌کند: `cached_balance_toman` کش جمع
  `ledger_entries` همان کیف پول است، فقط از راه `Wallet::applyLedgerEntry()`
  نوشته می‌شود (خارج از `$fillable`، محافظت‌شده با
  `Model::preventSilentlyDiscardingAttributes()` در `AppServiceProvider`).
  `php artisan fbh:reconcile-wallets` این کش را در برابر جمع واقعی می‌سنجد.

## مرز با ماژول‌های دیگر: `App\Contracts\LedgerRecorder`

هیچ ماژولی مدل `LedgerAccount` یا `LedgerTransaction` را import نمی‌کند
(قاعده ۱). به‌جایش:

```php
app(\App\Contracts\LedgerRecorder::class)->record(new LedgerTransactionRequest(
    kind: 'wallet.manual_topup',
    idempotencyKey: $key,
    entries: [
        new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, $amount),
        new LedgerEntryLine(LedgerAccountRef::wallet($userId), EntryDirection::Credit, $amount),
    ],
));
```

`AccountType`، `EntryDirection` و DTOهای `LedgerAccountRef` / `LedgerEntryLine`
/ `LedgerTransactionRequest` / `LedgerReceipt` در `App\Support\Ledger` هستند،
نه داخل این ماژول: هر ماژولی که تراکنش ثبت می‌کند باید بتواند نوع حساب و
جهت ردیف را مشخص کند، و وارد کردن Enum از فضای‌نام ماژول Ledger همین قاعده ۱
را می‌شکست.

اگر این ماژول خاموش باشد، قرارداد `LedgerRecorder` بسته نمی‌شود و مصرف‌کننده
باید پیش از فراخوانی با `app()->bound()` بررسی کند.

## نوع حساب ششم: خزانه (`Treasury`)

ADR-0003 پنج نوع حساب تعریف کرده (کیف پول کاربر، درآمد پلتفرم، بدهی به
فروشنده، امانت وجه پروژه، حساب واسط درگاه) که هیچ‌کدام «طرف مقابل» شارژ دستی
نیستند. شارژ دستی یعنی پول از بیرون سیستم می‌آید و باید یک‌جا بدهکار شود تا
تراکنش صفر شود؛ `AccountType::Treasury` همین نقش را دارد. این تصمیم به‌عنوان
**DEC-20** در `docs/decisions-pending.md` ثبت شده و منتظر تأیید مدیر است —
تا آن زمان همین‌طور که هست کار می‌کند.

## شارژ دستی کیف پول

تنها راه ورود پول به سیستم در نسخه یک (ADR-0003: انتقال بین کیف‌پول‌ها،
کش‌بک و پرداخت ترکیبی صراحتاً بیرون از فاز یک‌اند). `Actions/CreditWalletManually`
تراکنش خزانه↔کیف‌پول را می‌سازد؛ صفحه پنل مدیریت (`admin/wallet-topup`،
توانایی از پیش موجود `admin.wallet.manage`، نقش‌های `Finance` و `Super`)
کاربر را با شماره موبایل پیدا می‌کند.

## دفتر رویداد

هر نوشتن **تازه** (نه بازپخش idempotent) رویداد `LedgerTransactionRecorded`
را منتشر می‌کند که `App\Contracts\AuditableEvent` را پیاده می‌کند؛ ماژول
Core خودش آن را در `audit_logs` ثبت می‌کند (قاعده ۷). مبلغ و جهت ردیف‌ها
نوشته می‌شود، مالک حساب‌ها نه — مبلغ داده حساس شخصی نیست، شناسه کاربر پشت
حساب هست.

## دستورها

```bash
php artisan fbh:reconcile-wallets           # گزارش مغایرت کش با دفتر کل
php artisan fbh:reconcile-wallets --apply   # اصلاح کش با عدد واقعی
```

## آنچه در این بخش ساخته نشد

- **کمیسیون خودکار و تسویه فروشنده/کارشناس.** `CommissionService` واحد و
  آداپتور درگاه پرداخت (ZarinPal، پشت `App\Contracts\PaymentGateway`) بخش
  بعدی‌اند؛ این بخش فقط زیرساخت دفتر کل را می‌سازد که آن بخش رویش بنا شود.
- **انتقال بین کیف‌پول‌ها، کش‌بک و پرداخت ترکیبی.** ADR-0003 صریحاً این سه
  را از فاز یک بیرون گذاشته.
- **برگشت/استرداد.** ADR می‌گوید برگشت همیشه تراکنش برگشتی تازه می‌سازد؛
  چون هنوز هیچ عملیات مالی قابل‌برگشتی (مثل پرداخت دوره) ساخته نشده،
  اکشن برگشت هم ساخته نشده — منطقش با اولین مصرف‌کننده واقعی می‌آید.
