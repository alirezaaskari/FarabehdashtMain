<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Vibration;

use Farabehdasht\CalcEngine\Input\InputError;
use Farabehdasht\CalcEngine\Input\InputErrorCode;
use Farabehdasht\CalcEngine\Input\InputSet;

/**
 * بررسی مشترک دو فرمول ارتعاش: هر ردیف مقدار دارد و مدت، و مدت کل صفر نیست.
 */
final class Durations
{
    /**
     * @param  string|list<string>  $keys  فهرست‌هایی که باید هم‌اندازه مدت‌ها باشند
     * @return list<InputError>
     */
    public static function check(InputSet $inputs, string|array $keys, string $noun): array
    {
        $durations = $inputs->values('durations');

        $errors = [];

        foreach ((array) $keys as $key) {
            $count = count($inputs->values($key));

            if ($count !== count($durations)) {
                $errors[] = new InputError(
                    'durations',
                    InputErrorCode::TooFewItems,
                    sprintf(
                        'تعداد مدت‌ها (%d) با تعداد %s (%d) یکی نیست؛ هر کار باید هر دو را داشته باشد.',
                        count($durations),
                        $noun,
                        $count,
                    ),
                    ['expected' => $count, 'given' => count($durations)],
                );

                break;
            }
        }

        if (array_sum($durations) <= 0.0) {
            $errors[] = new InputError(
                'durations',
                InputErrorCode::OutOfRange,
                'مجموع مدت کارها باید بیشتر از صفر باشد.',
                ['given' => array_sum($durations)],
            );
        }

        return $errors;
    }
}
