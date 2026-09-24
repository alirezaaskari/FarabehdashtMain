<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Support\Money;
use DateTimeInterface;

/**
 * سازنده داده ساختاریافته schema.org.
 *
 * قاعده محصول که این کلاس نگه می‌دارد: هیچ داده ساختاریافته‌ای ادعای تأیید
 * رسمی نمی‌کند. دوره‌ها `Course` هستند نه `EducationalOccupationalCredential`،
 * و هیچ‌جا `accreditedBy` نوشته نمی‌شود.
 *
 * فقط نوع‌هایی ساخته می‌شوند که واقعاً در سایت وجود دارند؛ Schema اختراعی که
 * با محتوای صفحه نخواند، جریمه سئو دارد نه امتیاز. به همین دلیل امتیاز و نظر
 * (`AggregateRating`، `Review`) هم ساخته نمی‌شود: امتیازی که سایت درباره
 * محتوای خودش می‌دهد خوداظهاری است (DEC-30).
 *
 * صفحه‌ای که بیش از یک نوع دارد (مقاله + مسیر راهنما) آن‌ها را با `graph()`
 * در یک بلوک می‌گذارد، نه چند تگ script جدا.
 */
final readonly class Schema
{
    private const CONTEXT = 'https://schema.org';

    // واحد پایه تومان است و ISO کدی برای تومان ندارد؛ IRT کد غیررسمی ولی
    // جاافتاده تومان است و با ستون‌های _toman یکی است.
    private const CURRENCY = 'IRT';

    /**
     * @param  list<string>  $authors
     * @return array<string, mixed>
     */
    public static function article(
        string $headline,
        string $url,
        DateTimeInterface $publishedAt,
        ?DateTimeInterface $updatedAt = null,
        ?string $description = null,
        ?string $image = null,
        array $authors = [],
    ): array {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Article',
            'headline' => $headline,
            'url' => $url,
            'description' => $description,
            'image' => $image,
            'datePublished' => $publishedAt->format(DATE_ATOM),
            'dateModified' => ($updatedAt ?? $publishedAt)->format(DATE_ATOM),
            'author' => array_map(
                static fn (string $name): array => ['@type' => 'Person', 'name' => $name],
                $authors,
            ) ?: null,
            'publisher' => self::organization(),
            'inLanguage' => 'fa-IR',
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    public static function course(
        string $name,
        string $url,
        string $description,
        ?string $instructor = null,
        ?Money $price = null,
    ): array {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Course',
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'provider' => self::organization(),
            'inLanguage' => 'fa-IR',
            'instructor' => $instructor === null ? null : ['@type' => 'Person', 'name' => $instructor],
            'offers' => $price === null ? null : [
                '@type' => 'Offer',
                'category' => $price->isZero() ? 'Free' : 'Paid',
                'price' => $price->toman,
                'priceCurrency' => self::CURRENCY,
            ],
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function product(string $name, string $url, Money $price, bool $inStock = true, ?string $description = null): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Product',
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'offers' => [
                '@type' => 'Offer',
                'price' => $price->toman,
                'priceCurrency' => self::CURRENCY,
                'availability' => $inStock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * ابزار محاسباتی. رایگان است و همین را صریح می‌گوید.
     *
     * @return array<string, mixed>
     */
    public static function webApplication(string $name, string $url, string $description): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'WebApplication',
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'All',
            'browserRequirements' => 'Requires a modern web browser',
            'isAccessibleForFree' => true,
            'offers' => ['@type' => 'Offer', 'price' => 0, 'priceCurrency' => self::CURRENCY],
            'provider' => self::organization(),
            'inLanguage' => 'fa-IR',
        ];
    }

    /**
     * یک اصطلاح واژه‌نامه، عضو مجموعه واژه‌نامه سایت.
     *
     * @return array<string, mixed>
     */
    public static function definedTerm(string $name, string $url, ?string $description, string $setName, string $setUrl): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'DefinedTerm',
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'inDefinedTermSet' => ['@type' => 'DefinedTermSet', 'name' => $setName, 'url' => $setUrl],
            'inLanguage' => 'fa-IR',
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * ماده شیمیایی — فقط شناسه‌ها. حد مواجهه عمداً در Schema نمی‌آید: عددی
     * که بیرون از صفحه و بدون منبع و تاریخش دیده شود، ادعای ایمنی است.
     *
     * @return array<string, mixed>
     */
    public static function chemicalSubstance(string $name, string $url, string $cas, ?string $alternateName = null, ?string $formula = null): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'ChemicalSubstance',
            'name' => $name,
            'alternateName' => $alternateName,
            'url' => $url,
            'identifier' => ['@type' => 'PropertyValue', 'propertyID' => 'CAS', 'value' => $cas],
            'chemicalComposition' => $formula,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    public static function webPage(string $name, string $url, ?DateTimeInterface $updatedAt = null): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'WebPage',
            'name' => $name,
            'url' => $url,
            'dateModified' => $updatedAt?->format(DATE_ATOM),
            'publisher' => self::organization(),
            'inLanguage' => 'fa-IR',
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * خود سایت، با جست‌وجوی داخلی اگر مسیرش باشد. فقط روی صفحه اصلی.
     *
     * @param  string|null  $searchUrl  نشانی جست‌وجو که `{search_term_string}` در آن جایگزین می‌شود
     * @return array<string, mixed>
     */
    public static function website(?string $searchUrl = null): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'WebSite',
            'name' => (string) config('app.name'),
            'url' => (string) config('app.url'),
            'inLanguage' => 'fa-IR',
            'publisher' => self::organization(),
            'potentialAction' => $searchUrl === null ? null : [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $searchUrl],
                'query-input' => 'required name=search_term_string',
            ],
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * چند نوع در یک بلوک JSON-LD.
     *
     * @param  array<string, mixed>  ...$nodes
     * @return array<string, mixed>
     */
    public static function graph(array ...$nodes): array
    {
        return [
            '@context' => self::CONTEXT,
            '@graph' => array_map(static function (array $node): array {
                unset($node['@context']);

                return $node;
            }, array_values($nodes)),
        ];
    }

    /**
     * @param  list<array{question: string, answer: string}>  $items
     * @return array<string, mixed>
     */
    public static function faq(array $items): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
            ], $items),
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $trail
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $trail): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (int $index, array $item): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ],
                array_keys($trail),
                $trail,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        return [
            '@type' => 'Organization',
            'name' => (string) config('app.name'),
            'url' => (string) config('app.url'),
        ];
    }
}
