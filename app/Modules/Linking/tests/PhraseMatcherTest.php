<?php

declare(strict_types=1);

namespace App\Modules\Linking\Tests;

use App\Modules\Linking\Domain\PhraseMatch;
use App\Modules\Linking\Services\PhraseMatcher;
use App\Support\Linking\LinkTarget;
use PHPUnit\Framework\TestCase;

final class PhraseMatcherTest extends TestCase
{
    public function test_it_finds_a_phrase_and_reports_its_place_in_the_original_text(): void
    {
        $text = 'مواجهه با بنزن در پالایشگاه';
        $matches = PhraseMatcher::for(['chemicals:benzene' => ['بنزن']])->find($text);

        $this->assertCount(1, $matches);
        $this->assertSame('بنزن', $this->cut($text, $matches[0]));
        $this->assertSame('chemicals:benzene', $matches[0]->targetKey);
    }

    public function test_arabic_letters_diacritics_and_persian_digits_do_not_hide_a_match(): void
    {
        $matcher = PhraseMatcher::for([
            'glossary:dose' => ['دز صدا'],
            'chemicals:benzene' => ['71-43-2'],
        ]);

        // «ي» عربی، تشدید و ارقام فارسی — همه در متن اصلی می‌مانند.
        $text = 'دزّ صداي روزانه و شماره ۷۱-۴۳-۲';
        $matches = $matcher->find($text);

        $this->assertSame(['دزّ صداي', '۷۱-۴۳-۲'], array_map(fn (PhraseMatch $m): string => $this->cut($text, $m), $matches));
    }

    public function test_a_phrase_inside_a_longer_word_is_not_a_match(): void
    {
        $matcher = PhraseMatcher::for(['glossary:sound' => ['صوت']]);

        $this->assertSame([], $matcher->find('آلودگی صوتی کارگاه'));
        $this->assertSame([], $matcher->find("دستگاه صوت\u{200C}سنج"), 'نیم‌فاصله کلمه را نمی‌شکند.');
        $this->assertCount(1, $matcher->find('شدت صوت، بالا'));
    }

    public function test_attached_suffixes_belong_to_the_linked_word(): void
    {
        $matcher = PhraseMatcher::for(['chemicals:benzene' => ['بنزن'], 'glossary:noise' => ['صدا']]);

        $text = "بخار بنزن\u{200C}ها و صدای دستگاه";
        $matches = $matcher->find($text);

        $this->assertSame(["بنزن\u{200C}ها", 'صدای'], array_map(fn (PhraseMatch $m): string => $this->cut($text, $m), $matches));
        $this->assertSame('بنزن', $matches[0]->phrase);
    }

    public function test_space_and_half_space_are_the_same_between_the_words_of_a_phrase(): void
    {
        $matcher = PhraseMatcher::for(['glossary:mask' => ['ماسک نیم صورت']]);

        $this->assertCount(1, $matcher->find("استفاده از ماسک نیم\u{200C}صورت"));
        $this->assertCount(1, $matcher->find('استفاده از ماسک   نیم صورت'));
    }

    public function test_the_longest_phrase_wins(): void
    {
        $matcher = PhraseMatcher::for([
            'glossary:sound' => ['صوت'],
            'glossary:spl' => ['تراز فشار صوت'],
        ]);

        $matches = $matcher->find('تراز فشار صوت را اندازه بگیرید');

        $this->assertCount(1, $matches);
        $this->assertSame('glossary:spl', $matches[0]->targetKey);
    }

    public function test_latin_names_match_regardless_of_case(): void
    {
        $matches = PhraseMatcher::for(['chemicals:toluene' => ['Toluene']])->find('حلال TOLUENE در رنگ');

        $this->assertCount(1, $matches);
    }

    public function test_a_phrase_claimed_by_two_targets_is_ambiguous_and_dropped(): void
    {
        $matcher = PhraseMatcher::for([
            'chemicals:ethanol' => ['الکل'],
            'chemicals:methanol' => ['الکل', 'متانول'],
        ]);

        $matches = $matcher->find('الکل یا متانول');

        $this->assertCount(1, $matches);
        $this->assertSame('chemicals:methanol', $matches[0]->targetKey);
    }

    public function test_too_short_phrases_are_ignored(): void
    {
        $this->assertTrue(PhraseMatcher::for(['x:y' => ['گرد']], minLength: 4)->isEmpty());
    }

    public function test_many_phrases_are_split_across_patterns_without_losing_any(): void
    {
        $targets = [];

        foreach (range(1, 600) as $i) {
            $targets["t:{$i}"] = ["ماده{$i}"];
        }

        $matches = PhraseMatcher::for($targets)->find('ماده1 و ماده599 و ماده600');

        $this->assertSame(['t:1', 't:599', 't:600'], array_map(static fn (PhraseMatch $m): string => $m->targetKey, $matches));
    }

    public function test_a_title_with_a_subtitle_also_links_by_its_head(): void
    {
        $this->assertSame(
            ['اندازه‌گیری صدا در محیط کار — روش و تجهیزات', 'اندازه‌گیری صدا در محیط کار'],
            LinkTarget::titlePhrases('اندازه‌گیری صدا در محیط کار — روش و تجهیزات'),
        );
        $this->assertSame(['دز صدا'], LinkTarget::titlePhrases('دز صدا'));
    }

    private function cut(string $text, PhraseMatch $match): string
    {
        return mb_substr($text, $match->start, $match->length);
    }
}
