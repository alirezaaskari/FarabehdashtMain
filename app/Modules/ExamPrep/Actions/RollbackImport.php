<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use RuntimeException;

/** فقط برای برگرداندن تراکنش پیش‌نمایش یا ورود ناقص؛ بیرون از این پوشه دیده نمی‌شود. */
final class RollbackImport extends RuntimeException {}
