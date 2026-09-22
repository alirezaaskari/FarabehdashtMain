<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ثبت‌نام دانشجو در دوره — سفارش و ردیف سفارش یک‌جا، چون هر ثبت‌نام دقیقاً
 * یک دوره است (بر خلاف سبد چندمحصولی فروشگاه). قیمت و کمیسیون لحظه خرید
 * روی همین ردیف Snapshot می‌شوند، دقیقاً مثل order_items ماژول تجارت.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->restrictOnDelete();

            $table->string('status', 16)->default('pending');
            $table->unsignedBigInteger('price_toman');
            $table->unsignedInteger('commission_rate_bp');
            $table->unsignedBigInteger('commission_toman');
            $table->unsignedBigInteger('instructor_amount_toman');

            $table->string('gateway_authority')->nullable()->unique();
            $table->string('gateway_ref_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['course_id', 'student_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
