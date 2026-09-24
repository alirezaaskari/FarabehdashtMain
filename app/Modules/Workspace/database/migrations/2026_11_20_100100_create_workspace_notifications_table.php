<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اعلان درون‌سایتی (DEC-22). مقصد نام مسیر است، نه نشانی: اگر ماژول
        // صاحب مسیر خاموش شود، اعلان می‌ماند و فقط پیوندش پنهان می‌شود.
        Schema::create('workspace_notifications', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('kind', 64);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('route_name')->nullable();
            $table->json('route_parameters')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_notifications');
    }
};
