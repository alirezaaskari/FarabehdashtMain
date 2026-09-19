<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Models\User;
use App\Modules\Admin\Services\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * شروع و پایان «مشاهده به‌عنوان کاربر».
 *
 * مسیر پایان عمداً بیرون از پنل است: مدیر در این حالت داخل میزکار کاربر است،
 * نه داخل پنل، و باید از همان‌جا بتواند برگردد.
 */
final readonly class ImpersonationController
{
    public function __construct(private Impersonation $impersonation) {}

    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin instanceof User, 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:'.config('admin.impersonation.min_reason_length', 10), 'max:255'],
        ], [
            'reason.required' => 'نوشتن دلیل اجباری است.',
            'reason.min' => 'دلیل باید روشن باشد؛ چند کلمه بیشتر بنویسید.',
        ]);

        try {
            $this->impersonation->start($admin, $user, $validated['reason']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['reason' => $exception->getMessage()]);
        }

        return redirect('/')->with('status', 'اکنون سایت را از چشم این کاربر می‌بینید.');
    }

    public function stop(): RedirectResponse
    {
        try {
            $this->impersonation->stop();
        } catch (RuntimeException $exception) {
            return redirect('/')->withErrors(['impersonation' => $exception->getMessage()]);
        }

        return redirect('/'.config('admin.path', 'fbh-panel'))
            ->with('status', 'به حساب مدیریتی خودتان برگشتید.');
    }
}
