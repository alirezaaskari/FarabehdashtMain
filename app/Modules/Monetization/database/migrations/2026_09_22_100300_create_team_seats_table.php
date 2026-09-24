<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // صندلی تیمی فقط اشتراک را به حساب مستقل یک عضو اضافه می‌کند؛ هیچ
        // پنل سازمانی و هیچ مالکیتی روی داده عضو در کار نیست (مرز صریح
        // docs/roadmap/revenue-additions.md).
        Schema::create('team_seats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('member_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->unique(['subscription_id', 'member_user_id']);
            $table->index(['member_user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_seats');
    }
};
