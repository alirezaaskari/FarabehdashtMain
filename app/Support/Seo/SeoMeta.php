<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * فراداده سئوی یک صفحه.
 *
 * چرا شیء و نه چند متغیر پراکنده در Blade: عنوان، توضیح و Canonical باید با هم
 * تصمیم‌گیری شوند. صفحه‌ای که noindex است Canonical لازم ندارد و صفحه‌ای که
 * Canonical دارد نباید در Sitemap دو بار بیاید.
 *
 * قاعده محصول: صفحات میزکار و ابزار همیشه noindex هستند.
 */
final readonly class SeoMeta
{
    /**
     * @param  string  $title  عنوان صفحه، بدون نام سایت — چیدن نام سایت کار چیدمان است
     * @param  string|null  $description  توضیح ۱۲۰ تا ۱۶۰ نویسه‌ای
     * @param  string|null  $canonical  نشانی اصلی این محتوا، اگر با نشانی جاری فرق دارد
     * @param  bool  $noindex  صفحه نباید ایندکس شود
     * @param  string|null  $image  نشانی تصویر اشتراک‌گذاری
     * @param  array<string, mixed>|null  $schema  داده ساختاریافته schema.org
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $canonical = null,
        public bool $noindex = false,
        public ?string $image = null,
        public ?array $schema = null,
    ) {}

    public function noindexed(): self
    {
        return new self($this->title, $this->description, null, true, $this->image, $this->schema);
    }

    /** @param  array<string, mixed>  $schema */
    public function withSchema(array $schema): self
    {
        return new self($this->title, $this->description, $this->canonical, $this->noindex, $this->image, $schema);
    }

    public function withCanonical(string $canonical): self
    {
        return new self($this->title, $this->description, $canonical, $this->noindex, $this->image, $this->schema);
    }
}
