<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Console;

use App\Models\User;
use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * محتوای نمونه دانشنامه، برای محیط توسعه و بازبینی چشمی.
 *
 * عمداً Seeder معمولی نیست: `db:seed` در استقرار اجرا می‌شود و محتوای نمونه
 * هرگز نباید روی سایت زنده بنشیند. این دستور باید صریح صدا زده شود و در
 * محیط production کار نمی‌کند.
 */
final class SeedEncyclopediaCommand extends Command
{
    protected $signature = 'fbh:seed-encyclopedia';

    protected $description = 'ساخت محتوای نمونه دانشنامه — فقط محیط توسعه';

    public function handle(PublishArticle $publish): int
    {
        if (app()->environment('production')) {
            $this->error('محتوای نمونه در محیط اصلی ساخته نمی‌شود.');

            return self::FAILURE;
        }

        $author = $this->user('نویسنده نمونه', '09120000101');
        $reviewer = $this->user('بازبین علمی نمونه', '09120000102');

        $created = 0;

        foreach ($this->content() as $draft) {
            if (Article::query()->where('slug', $draft['slug'])->exists()) {
                continue;
            }

            $article = Article::query()->create([
                'uuid' => (string) Str::uuid7(),
                'slug' => $draft['slug'],
                'type' => $draft['type'],
                'status' => ArticleStatus::Draft,
                'title' => $draft['title'],
                'summary' => $draft['summary'],
                'author_id' => $author->id,
                'reviewer_id' => $reviewer->id,
                'reviewed_at' => Carbon::now()->subDays($draft['reviewedDaysAgo']),
            ]);

            foreach ($draft['sections'] as $position => $section) {
                ArticleSection::query()->create([
                    'article_id' => $article->id,
                    'position' => $position + 1,
                    'heading' => $section['heading'],
                    'body' => $section['body'],
                    'note' => $section['note'] ?? null,
                    'tool_slug' => $section['tool'] ?? null,
                ]);
            }

            foreach ($draft['references'] as $position => $reference) {
                ArticleReference::query()->create([
                    'article_id' => $article->id,
                    'position' => $position + 1,
                    'title' => $reference['title'],
                    'publisher' => $reference['publisher'] ?? null,
                    'year' => $reference['year'] ?? null,
                ]);
            }

            try {
                $publish->handle($article->refresh());
                $created++;
            } catch (RuntimeException $exception) {
                $this->warn($draft['slug'].': '.$exception->getMessage());
            }
        }

        $this->info(sprintf('%d محتوای نمونه ساخته شد.', $created));

        return self::SUCCESS;
    }

    private function user(string $name, string $mobile): User
    {
        return User::query()->firstOrCreate(
            ['mobile' => $mobile],
            ['name' => $name, 'mobile_verified_at' => Carbon::now()],
        );
    }

    /** @return list<array<string, mixed>> */
    private function content(): array
    {
        return [
            [
                'slug' => 'noise-measurement-at-work',
                'type' => ArticleType::Method,
                'title' => 'اندازه‌گیری صدا در محیط کار — روش، تجهیزات و الزامات',
                'summary' => 'روش، تجهیزات، طراحی شبکه اندازه‌گیری و الزامات گزارش‌دهی پایش صدای شغلی.',
                'reviewedDaysAgo' => 40,
                'sections' => [
                    [
                        'heading' => 'چرا اندازه‌گیری صدا لازم است',
                        'body' => "افت شنوایی ناشی از سروصدا برگشت‌ناپذیر است و برخلاف بسیاری از عوامل زیان‌آور، علامت هشداردهنده زودرس ندارد. هدف پایش صدا تعیین میزان مواجهه واقعی شاغل در طول شیفت، شناسایی منابع اصلی، و اولویت‌بندی اقدامات کنترلی است — نه صرفاً ثبت یک عدد در پرونده.\n\nترتیب کنترل همواره از بالا به پایین است: حذف منبع، جایگزینی، کنترل مهندسی، کنترل اداری، و در آخر حفاظت فردی. انتخاب مستقیم گوشی حفاظتی بدون بررسی گزینه‌های بالاتر، یک خطای رایج و پرهزینه است.",
                        'note' => 'تراز فشار صوت لحظه‌ای معیار مواجهه نیست. آنچه با حد مجاز مقایسه می‌شود، تراز معادل هشت‌ساعته یا دوز مواجهه است.',
                    ],
                    [
                        'heading' => 'ترکیب چند منبع صوتی',
                        'body' => 'ترازهای صوتی لگاریتمی‌اند و با جمع ساده حسابی ترکیب نمی‌شوند. دو منبع هم‌تراز، تراز کل را حدود سه دسی‌بل بالا می‌برند و نه دو برابر.',
                        'tool' => 'sound-pressure-sum',
                    ],
                    [
                        'heading' => 'ثبت و گزارش نتایج',
                        'body' => 'هر گزارش باید مشخصات پروژه، شرایط محیطی، مدل و شماره سریال تجهیز، نتیجه کالیبراسیون، روش، ورودی‌ها، فرمول به‌کاررفته و نسخه آن، منابع، و محدودیت‌های اندازه‌گیری را شامل شود. گزارشی که مسیر رسیدن به عدد را نشان ندهد، قابل دفاع نیست.',
                    ],
                ],
                'references' => [
                    ['title' => 'ISO 9612 — Determination of occupational noise exposure', 'publisher' => 'ISO', 'year' => 2009],
                    ['title' => 'NIOSH Criteria for a Recommended Standard: Occupational Noise Exposure', 'publisher' => 'NIOSH', 'year' => 1998],
                ],
            ],
            [
                'slug' => 'heat-stress-control',
                'type' => ArticleType::Article,
                'title' => 'کنترل استرس گرمایی در صنایع فلزی',
                'summary' => 'ترتیب کنترل، برنامه کار و استراحت، و پایش شاخص WBGT در محیط‌های گرم.',
                'reviewedDaysAgo' => 500,
                'sections' => [
                    [
                        'heading' => 'شاخص WBGT چه می‌گوید و چه نمی‌گوید',
                        'body' => "شاخص WBGT شرایط محیط را توصیف می‌کند: دمای تر طبیعی، دمای گویسان و — در فضای باز آفتابی — دمای خشک هوا. آنچه نمی‌گوید، بار متابولیکی کار و نوع پوشش است.\n\nهمین است که قضاوت درباره مواجهه را بدون تعیین سطح فعالیت ناممکن می‌کند.",
                        'tool' => 'wbgt-indoor',
                    ],
                    [
                        'heading' => 'ترتیب اقدام کنترلی',
                        'body' => 'جداسازی منبع گرما، سپرگذاری تابشی، تهویه موضعی، و در آخر برنامه کار–استراحت. نوشیدن آب جایگزین هیچ‌کدام نیست؛ مکمل همه است.',
                        'note' => 'برنامه کار–استراحت کنترل اداری است و پایین‌ترین ردیف پیش از حفاظت فردی. جایگزین کنترل مهندسی نیست.',
                    ],
                    [
                        'heading' => 'پایش دوره‌ای',
                        'body' => 'اندازه‌گیری یک‌باره در یک روز گرم، تصویر نادرستی می‌دهد. پایش باید گرم‌ترین ساعت شیفت و گرم‌ترین ماه سال را پوشش بدهد و با همان روش تکرار شود.',
                    ],
                ],
                'references' => [
                    ['title' => 'ISO 7243 — Assessment of heat stress using the WBGT index', 'publisher' => 'ISO', 'year' => 2017],
                    ['title' => 'ACGIH TLVs and BEIs — Heat Stress and Strain', 'publisher' => 'ACGIH', 'year' => 2023],
                ],
            ],
            [
                'slug' => 'twa-stel-ceiling-idlh',
                'type' => ArticleType::Glossary,
                'title' => 'TWA، STEL، Ceiling و IDLH',
                'summary' => 'تفاوت چهار مفهوم پایه مواجهه شغلی و اینکه هرکدام کجا به‌کار می‌آیند.',
                'reviewedDaysAgo' => 20,
                'sections' => [
                    [
                        'heading' => 'میانگین وزنی‌زمانی و مواجهه کوتاه‌مدت',
                        'body' => "TWA میانگین مواجهه در طول شیفت هشت‌ساعته است و نوسان‌های کوتاه را در خود حل می‌کند. STEL همان میانگین است ولی روی پنجره پانزده‌دقیقه‌ای، و دقیقاً برای گرفتن همان نوسان‌هایی ساخته شده که TWA پنهانشان می‌کند.\n\nیک شیفت می‌تواند TWA قابل قبول و STEL غیرقابل قبول داشته باشد. هر دو باید جدا بررسی شوند.",
                        'tool' => 'twa-ppm',
                    ],
                    [
                        'heading' => 'حد سقف و حد خطر آنی',
                        'body' => 'Ceiling حدی است که حتی لحظه‌ای نباید از آن گذشت. IDLH غلظتی است که فرار بدون آسیب برگشت‌ناپذیر را ناممکن می‌کند و معیار انتخاب تجهیز حفاظت تنفسی در وضعیت اضطراری است، نه معیار پایش روزمره.',
                        'note' => 'IDLH حد مجاز مواجهه نیست. به‌کاربردنش به‌جای TWA یا STEL، یک خطای تفسیری جدی است.',
                    ],
                    [
                        'heading' => 'واحد و تبدیل',
                        'body' => 'حد مجاز گاهی بر حسب ppm و گاهی mg/m³ اعلام می‌شود. تبدیل به جرم مولکولی، دما و فشار محل وابسته است و ضریب ثابت ندارد.',
                        'tool' => 'ppm-to-mass-concentration',
                    ],
                ],
                'references' => [
                    ['title' => 'حدود مجاز مواجهه شغلی ایران', 'publisher' => 'وزارت بهداشت، درمان و آموزش پزشکی', 'year' => 1400],
                    ['title' => 'NIOSH Pocket Guide to Chemical Hazards', 'publisher' => 'NIOSH', 'year' => 2007],
                ],
            ],
        ];
    }
}
