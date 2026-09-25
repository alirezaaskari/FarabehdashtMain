# پرسش از متخصص — `Modules/Expert`

بخش ۱۸-۳ نسخه ۱٫۱. کاربر واردشده پرسش تخصصی بهداشت حرفه‌ای می‌پرسد و مشاوران
تأییدشده پاسخ می‌دهند. **پرسش و پاسخ هر دو پیش از دیده‌شدن تأیید مدیر می‌خواهند.**

## جریان

```
AskQuestion (Pending) → مدیر: ReviewQuestion::publish | reject(یادداشت)
   → مشاور: SubmitAnswer (Pending) → مدیر: ReviewAnswer::publish | reject(یادداشت)
   → پرسش‌کننده: AcceptAnswer → پرسش «پاسخ‌گرفته»
```

- پاسخ‌دهنده کسی است که توانایی `expert.answer` دارد، یعنی پروفایل **مشاور**
  تأییدشده (پیکربندی مجوزهای ماژول هویت). این ماژول چیزی از هویت import نمی‌کند.
- هر مشاور یک پاسخ به هر پرسش دارد؛ پاسخ برگشتی با یادداشت مدیر اصلاح و دوباره فرستاده
  می‌شود. مشاور به پرسش خودش پاسخ نمی‌دهد.
- رد یا برگرداندن بدون یادداشت پذیرفته نیست.

## تصمیم‌ها

- **DEC-40:** پرسیدن رایگان است. مشترک Pro «اولویت» می‌گیرد: `priority` هنگام پرسیدن
  از `EntitlementGate` خوانده می‌شود و فقط دلیل `Subscribed` اولویت می‌دهد (کلید اشتراک
  خاموش یعنی امکان برای همه باز است، نه اینکه همه اولویت دارند). صف مشاور و صف مدیر با
  `inQueueOrder()` مرتب می‌شوند.
- **DEC-41:** نام پرسش‌کننده هیچ‌جا نمایش داده نمی‌شود، حتی در پنل. پرسش «فقط برای من و
  پاسخ‌دهنده» نه در فهرست است، نه در جست‌وجو، نه در نقشه سایت و نه در موتور پیوند؛
  صفحه‌اش `noindex` است و جز پرسش‌کننده، مشاوران و مدیر، برای بقیه ۴۰۴ می‌دهد
  (`QuestionAccess`).
- **DEC-42:** زیر نام پاسخ‌دهنده نشان «مشاور تأییدشده در فرابهداشت» و زیر هر پاسخ هشدار
  «این پاسخ نظر کارشناسی است؛ تشخیص پزشکی یا تأیید انطباق قانونی نیست.» می‌آید.
- پیوست پرسش در این تحویل ساخته نشد: فایل کاربر نگه‌داری خصوصی و بررسی جدا می‌خواهد.

## اتصال‌ها (همه از راه برچسب کانتینر)

| قرارداد | کلاس |
|---|---|
| `SitemapSource` | `QuestionSitemapSource` — فقط پرسش عمومی تأییدشده |
| `SearchSource` | `QuestionSearch` |
| `LinkableContentSource` | `QuestionDocuments` — پرسش و پاسخ‌های منتشرشده، به ترتیب صفحه |
| `ApprovalQueueSource` | `PendingExpertItems` — صف یکپارچه داشبورد پنل |

رویدادها دفتر رویداد (`AuditableEvent`، بدون متن پرسش)، اعلان (`UserNotifiableEvent`) و
بازسازی پیوند (`LinkableContentChanged`) را می‌رسانند. پاسخ تازه در گروه پیامکی
«پاسخ تازه به پرسش شما» است (ماژول میزکار، `SmsTopic::Expert`).

Schema صفحه پرسش `QAPage` است (`Schema::qaPage`)، فقط وقتی دست‌کم یک پاسخ منتشرشده دارد.

## مسیرها

| مسیر | نام |
|---|---|
| `GET /ask` · `GET /ask/{uuid}` | `expert.index` · `expert.show` |
| `GET /ask/new` · `POST /ask` (۵ پرسش در ساعت) | `expert.create` · `expert.store` |
| `POST /ask/{uuid}/answers` (`can:expert.answer`) | `expert.answers.store` |
| `POST /ask/{uuid}/accept/{answer}` | `expert.accept` |
| `GET /workspace/questions` · `GET /workspace/expert` | `expert.mine` · `expert.queue` |
| پنل: «پرسش از متخصص» (`admin.content.review`) | `filament.fbh.pages.expert-review` |

## پیکربندی

`config/expert.php`: صفحه‌بندی (۲۰)، سقف پرسش در ساعت (۵)، کمینه و بیشینه طول عنوان، متن و پاسخ.

## حذف این ماژول

پوشه `app/Modules/Expert` و `'Expert'` در `config/modules.php` را بردارید. پیوند سربرگ و
ردیف‌های ستون کناری با `Route::has` خودشان می‌روند.
