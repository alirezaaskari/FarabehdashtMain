<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Services\HomePage;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

/**
 * صفحه اصلی.
 *
 * هیچ منطقی ندارد جز صدازدن سرویس (قاعده ۴): چه چیزی نشان داده شود را
 * ماژول‌ها تعیین می‌کنند، نه این کنترلر.
 */
final readonly class HomeController
{
    public function __invoke(HomePage $page): View
    {
        $faq = $this->faq();

        return view('core::home', ['rows' => $page->rows(), 'faq' => $faq, 'seo' => $this->seo($faq)]);
    }

    /** @param  list<array{question: string, answer: string}>  $faq */
    private function seo(array $faq): SeoMeta
    {
        $meta = new SeoMeta(
            title: 'میزکار بهداشت حرفه‌ای و ایمنی کار (HSE)',
            description: 'محاسبه عوامل زیان‌آور با منبع علمی، حد مجاز مواجهه شغلی مواد شیمیایی، گزارش‌ساز PDF، دانشنامه، دوره، آمادگی آزمون، مشاوره و کاریابی بهداشت حرفه‌ای و HSE.',
            canonical: route('home'),
        );

        // جست‌وجوی داخلی مال ماژول Workspace است و ممکن است خاموش باشد.
        $search = Route::has('workspace.search') ? route('workspace.search').'?q={search_term_string}' : null;

        return $meta->withSchema(Schema::graph(Schema::organization(), Schema::website($search), Schema::faq($faq)));
    }

    /**
     * پرسش‌های پرتکرار صفحه اصلی؛ همین متن هم دیده می‌شود و هم در FAQPage می‌آید.
     *
     * @return list<array{question: string, answer: string}>
     */
    private function faq(): array
    {
        return [
            ['question' => 'فرابهداشت چیست؟', 'answer' => 'میزکار آنلاین فارسی برای کارشناس بهداشت حرفه‌ای و ایمنی کار: ابزارهای محاسبه با منبع علمی، بانک مواد شیمیایی با حدود مواجهه چند مرجع، گزارش‌ساز، دانشنامه بازبینی‌شده، دوره و آمادگی آزمون، مشاوره و کاریابی.'],
            ['question' => 'استفاده از ابزارهای محاسبه رایگان است؟', 'answer' => 'بله. همه ابزارها و مقاله‌های دانشنامه رایگان‌اند. اشتراک حرفه‌ای برای ذخیره نامحدود محاسبه، پروژه و صدور گزارش PDF با کد بررسی است.'],
            ['question' => 'نتیجه ابزارها چقدر قابل اتکاست؟', 'answer' => 'هر فرمول منبع و نسخه مشخص دارد و کنار نتیجه نشان داده می‌شود. خروجی ابزارها کمک‌کارشناسی است و جای قضاوت کارشناس، تشخیص پزشکی یا تأیید قطعی انطباق را نمی‌گیرد.'],
            ['question' => 'حد مجاز مواجهه شغلی هر ماده را از کجا ببینم؟', 'answer' => 'در بانک مواد شیمیایی، با نام فارسی، نام انگلیسی یا شماره CAS جست‌وجو کن. حدود چند مرجع کنار هم و هرکدام با منبع و سال آمده است.'],
            ['question' => 'کارجو برای دیدن آگهی یا فرستادن درخواست پول می‌دهد؟', 'answer' => 'هرگز. دیدن آگهی‌های شغلی و فرستادن درخواست رایگان است و حضور در بانک رزومه فقط با اجازه خود کارجوست.'],
            ['question' => 'گواهی دوره‌ها مدرک رسمی است؟', 'answer' => 'نه. گواهی و نشان‌های داخلی فرابهداشت نشان تکمیل دوره در همین سایت‌اند و مدرک رسمی یا مجوز حرفه‌ای محسوب نمی‌شوند.'],
        ];
    }
}
