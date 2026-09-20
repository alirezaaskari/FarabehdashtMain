<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دفترچه تجهیزات.
 *
 * کاربر تجهیزاتش را یک‌بار ثبت می‌کند و از آن به بعد مشخصاتش خودکار در گزارش
 * درج می‌شود. ثبت تجهیز این‌جا جایگزین گواهی کالیبراسیون رسمی نیست و صحت
 * داده‌های واردشده بر عهده کاربر است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            // کلاس دقت دستگاه (مثلاً «کلاس ۱» برای صداسنج). رشته است و نه
            // Enum، چون هر خانواده تجهیز نام‌گذاری خودش را دارد.
            $table->string('accuracy_class')->nullable();

            $table->date('calibrated_on')->nullable();
            $table->date('calibration_valid_until')->nullable();

            // مرجعی که کالیبراسیون را انجام داده — در گزارش درج می‌شود.
            $table->string('calibration_reference')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'calibration_valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
