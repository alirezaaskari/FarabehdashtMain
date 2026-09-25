<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Writing;

use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Support\PersianDigits;
use Illuminate\Support\Collection;

/**
 * برگردان متن ساده نویسنده به بخش‌ها و منابع ساختاریافته، و برعکس.
 *
 * نویسنده فرم تکرارشونده پنل را ندارد؛ یک متن می‌نویسد که هر سطر «## » در
 * آن یک بخش تازه باز می‌کند، و منابع را سطر به سطر با «|» جدا می‌کند. مدیر
 * بعداً همین‌ها را در ویرایشگر پنل بخش به بخش می‌بیند.
 */
final class DraftText
{
    private const HEADING = '## ';

    private const INTRO = 'مقدمه';

    /** @return list<array{heading: string, body: string}> */
    public static function sections(string $text): array
    {
        $sections = [];
        $heading = self::INTRO;
        $body = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            if (str_starts_with(ltrim($line), self::HEADING)) {
                $sections[] = ['heading' => $heading, 'body' => implode("\n", $body)];
                $heading = trim(substr(ltrim($line), strlen(self::HEADING)));
                $body = [];

                continue;
            }

            $body[] = $line;
        }

        $sections[] = ['heading' => $heading, 'body' => implode("\n", $body)];

        return array_values(array_filter(
            array_map(static fn (array $s): array => ['heading' => $s['heading'] !== '' ? $s['heading'] : self::INTRO, 'body' => trim($s['body'])], $sections),
            static fn (array $s): bool => $s['body'] !== '',
        ));
    }

    /** @param  Collection<int, ArticleSection>  $sections */
    public static function fromSections(Collection $sections): string
    {
        return $sections
            ->map(static fn (ArticleSection $section): string => self::HEADING.$section->heading."\n\n".$section->body)
            ->implode("\n\n");
    }

    /**
     * هر سطر: عنوان | ناشر | ویرایش | سال | نشانی — فقط عنوان لازم است.
     *
     * @return list<array{title: string, publisher: ?string, edition: ?string, year: ?int, url: ?string}>
     */
    public static function references(string $text): array
    {
        $rows = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));

            if (($parts[0] ?? '') === '') {
                continue;
            }

            $year = PersianDigits::toLatin($parts[3] ?? '');

            $rows[] = [
                'title' => $parts[0],
                'publisher' => ($parts[1] ?? '') !== '' ? $parts[1] : null,
                'edition' => ($parts[2] ?? '') !== '' ? $parts[2] : null,
                'year' => ctype_digit($year) ? (int) $year : null,
                'url' => ($parts[4] ?? '') !== '' ? $parts[4] : null,
            ];
        }

        return $rows;
    }

    /** @param  Collection<int, ArticleReference>  $references */
    public static function fromReferences(Collection $references): string
    {
        return $references
            ->map(static fn (ArticleReference $reference): string => rtrim(implode(' | ', [
                $reference->title,
                $reference->publisher ?? '',
                $reference->edition ?? '',
                $reference->year ?? '',
                $reference->url ?? '',
            ]), ' |'))
            ->implode("\n");
    }
}
