# ماژول ExamPrep — آمادگی آزمون

## این ماژول چه می‌کند

بسته‌های پولی آمادگی آزمون (بخش ۱۸-۷): بانک سؤال به تفکیک بسته، موضوع و سختی؛ نمونه رایگان ۱۰ سؤالی
(DEC-46)؛ تمرین با پاسخ فوری؛ آزمون زمان‌دار؛ کارنامه با ضعف هر موضوع و پیوند مطالعه برای هر پاسخ نادرست.
هیچ متنی وعده قبولی نمی‌دهد (`Services/NoPromises`، واژه‌ها در `config/exam_prep.php`).

- **بسته** را فقط مدیر در پنل می‌سازد و قیمت می‌گذارد (`Filament/Pages/ExamPacksPage`). درآمد کامل به درآمد
  پلتفرم می‌رود؛ دفتر کل با کلید یکتای `exam_prep.pack_paid:<uuid>`.
- **سؤال** یا از CSV پنل می‌آید (همه یا هیچ، مستقیم منتشر) یا از میزکار مدرس (`courses.manage`) که تا تأیید
  مدیر در صف `ExamQuestionsPage` می‌ماند.
- **دسترسی** (`Services/PackAccess`): نمونه برای هر کاربر واردشده، تمرین و آزمون برای خریدار. کلید
  `SalesSwitch::EXAM_PACK` فقط خرید تازه را می‌بندد.
- **آزمون**: ترتیب سؤال‌ها هنگام شروع در `prep_attempts.question_ids` ثابت می‌شود. زمان واقعی سمت سرور است؛
  فرمی که پس از زمان + `grace_seconds` برسد نمره می‌گیرد و `late` علامت می‌خورد.

## وابستگی‌ها

- `App\Contracts\PaymentGateway`، `FinancialGuard`، `LedgerRecorder`، `SalesSwitch` و `App\Support\Payments\WalletCheckout`.
- صف تأیید پنل (`AdminServiceProvider::APPROVAL_SOURCES`) و نقشه سایت (`SitemapSource::TAG`) با برچسب کانتینر.
- اعلان و پیامک نویسنده از رویداد `QuestionReviewed` (`UserNotifiableEvent`)؛ دفتر رویداد از `AuditableEvent`.

## نقاط توسعه

- سهم نویسنده سؤال از فروش (فعلاً توافق جداگانه با مدیر).
- پیوست تصویر به سؤال.
- بسته‌های ترکیبی با دوره (۱۸-۸).

## حذف این ماژول

پوشه `app/Modules/ExamPrep` را حذف کنید، `'ExamPrep'` را از `config/modules.php` و نگاشت تست آن را از
`autoload-dev` در `composer.json` بردارید.
