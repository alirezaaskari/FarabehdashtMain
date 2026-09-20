<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حد مواجهه شغلی — چندمرجعی و نسخه‌دار.
 *
 * ستون‌های منبع (`reference_title`، `reference_edition`، `reference_year`)
 * **NOT NULL نیستند ولی بی‌اهمیت هم نیستند**: قاعده «حد مواجهه بدون منبع
 * منتشر نمی‌شود» در اکشن انتشار است، چون شرطش وضعیتِ ماده است نه وجود ردیف.
 * پیش‌نویسی که هنوز منبعش وارد نشده باید بتواند ذخیره شود.
 *
 * یکتایی روی (ماده، مرجع، نوع) است: ACGIH برای تولوئن یک TWA دارد، نه دو تا.
 * دو ردیف یعنی یکی از آن‌ها کهنه است و کاربر نمی‌داند کدام.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exposure_limits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('substance_id')->constrained('substances')->cascadeOnDelete();

            $table->string('authority', 32);
            $table->string('type', 32);

            $table->decimal('value', 12, 4);
            $table->string('unit', 32);

            // یادداشت مرجع، مثل «با نشان پوستی» یا «قابل استنشاق».
            $table->string('note')->nullable();

            $table->string('reference_title')->nullable();
            $table->string('reference_edition', 64)->nullable();
            $table->unsignedSmallInteger('reference_year')->nullable();

            $table->timestamps();

            $table->unique(['substance_id', 'authority', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exposure_limits');
    }
};
