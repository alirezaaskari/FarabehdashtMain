<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پروژه اندازه‌گیری و ساختارش.
 *
 * چهار جدول، چون «مقایسه دو دور» بدون این تفکیک ممکن نیست:
 *
 *   projects            خودِ پروژه
 *   project_stations    ایستگاه‌های اندازه‌گیری — ستون‌های مقایسه
 *   project_rounds      دورهای اندازه‌گیری — مثلاً پیش و پس از اقدام کنترلی
 *   project_readings    قرائت هر ایستگاه در هر دور — خانه‌های جدول مقایسه
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('client_name')->nullable();
            $table->string('industry', 32)->nullable();
            $table->string('status', 16)->default('draft');
            $table->date('started_on')->nullable();
            $table->text('notes')->nullable();

            // تأیید صریح کاربر برای صدور گزارش با تجهیز بدون کالیبراسیون
            // معتبر. تاریخ است و نه بولین: باید معلوم باشد کِی تأیید شده، و
            // هر تغییر در فهرست تجهیزات آن را باطل می‌کند.
            $table->timestamp('equipment_warning_acknowledged_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('project_stations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('project_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('measured_on')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('project_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();

            // مقدار همین‌جا ذخیره می‌شود حتی وقتی از یک محاسبه آمده باشد.
            // وابسته‌نگه‌داشتنش به ماژول ابزارها یعنی اگر روزی آن ماژول خاموش
            // شود، پروژه‌های قدیمی عددشان را از دست بدهند.
            $table->double('value');
            $table->string('unit', 32);

            // ردِ محاسبه‌ای که این عدد از آن آمده، اگر آمده باشد.
            $table->uuid('calculation_uuid')->nullable();
            $table->string('tool_slug', 64)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // یک قرائت برای هر ایستگاه در هر دور — بیشتر یعنی داده مبهم.
            $table->unique(['project_round_id', 'project_station_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_readings');
        Schema::dropIfExists('project_rounds');
        Schema::dropIfExists('project_stations');
        Schema::dropIfExists('projects');
    }
};
