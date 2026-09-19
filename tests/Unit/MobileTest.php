<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Mobile;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * یک شماره، یک قالب ذخیره‌سازی. هر شکل ورودی دیگری باید به همان برسد،
 * وگرنه یک کاربر با دو حساب تکراری تمام می‌شود.
 */
final class MobileTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function sameNumberWrittenDifferently(): iterable
    {
        yield 'قالب استاندارد' => ['09121234567'];
        yield 'ارقام فارسی' => ['۰۹۱۲۱۲۳۴۵۶۷'];
        yield 'ارقام عربی' => ['٠٩١٢١٢٣٤٥٦٧'];
        yield 'پیش‌شماره بین‌المللی' => ['+989121234567'];
        yield 'پیش‌شماره با صفر دوتایی' => ['00989121234567'];
        yield 'بدون صفر ابتدایی' => ['9121234567'];
        yield 'با فاصله و خط تیره' => [' 0912-123 4567 '];
        yield 'بین‌المللی با ارقام فارسی' => ['+۹۸۹۱۲۱۲۳۴۵۶۷'];
    }

    #[DataProvider('sameNumberWrittenDifferently')]
    public function test_every_written_form_normalizes_to_one_value(string $input): void
    {
        $this->assertSame('09121234567', Mobile::fromInput($input)->value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidInputs(): iterable
    {
        yield 'خالی' => [''];
        yield 'کوتاه' => ['0912123456'];
        yield 'بلند' => ['091212345678'];
        yield 'تلفن ثابت' => ['02188776655'];
        yield 'بدون رقم' => ['سلام'];
        yield 'کد کشور نادرست' => ['+19121234567'];
    }

    #[DataProvider('invalidInputs')]
    public function test_an_invalid_number_is_refused(string $input): void
    {
        $this->assertNull(Mobile::normalize($input));
        $this->assertFalse(Mobile::isValid($input));
        $this->assertNull(Mobile::tryFromInput($input));
    }

    public function test_from_input_throws_on_an_invalid_number(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Mobile::fromInput('0912');
    }

    public function test_the_masked_form_hides_the_middle_digits(): void
    {
        $this->assertSame('0912***4567', Mobile::fromInput('09121234567')->masked());
    }

    public function test_it_casts_to_the_normalized_string(): void
    {
        $this->assertSame('09121234567', (string) Mobile::fromInput('+989121234567'));
    }
}
