<?php

declare(strict_types=1);

namespace App\Modules\Core\Seo;

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
 * با محتوای صفحه نخواند، جریمه سئو دارد نه امتیاز.
 */
final readonly class Schema
{
    private const CONTEXT = 'https://schema.org';

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
    public static function course(string $name, string $url, string $description, ?string $instructor = null): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Course',
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'provider' => self::organization(),
            'inLanguage' => 'fa-IR',
            'instructor' => $instructor === null ? null : ['@type' => 'Person', 'name' => $instructor],
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function product(string $name, string $url, Money $price, bool $inStock = true): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'Product',
            'name' => $name,
            'url' => $url,
            'offers' => [
                '@type' => 'Offer',
                'price' => $price->toman,
                // واحد پایه محصول تومان است و ISO کدی برای تومان ندارد؛ IRT
                // کد غیررسمی ولی جاافتاده تومان است و با ستون _toman یکی است.
                'priceCurrency' => 'IRT',
                'availability' => $inStock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
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
