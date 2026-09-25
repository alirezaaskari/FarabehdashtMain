<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Models\User;
use App\Modules\Identity\Actions\SignOutOtherSessions;
use App\Modules\Identity\Actions\UpdateAccount;
use App\Support\Mobile;
use App\Support\PersianDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** «حساب من»: نام، ایمیل، شماره ورود و نشست‌های باز. */
final readonly class AccountController
{
    public function __construct(
        private UpdateAccount $update,
        private SignOutOtherSessions $sessions,
    ) {}

    public function show(Request $request): View
    {
        $user = $this->user($request);

        return view('identity::account', [
            'user' => $user,
            'mobile' => Mobile::tryFromInput($user->mobile),
            'otherSessions' => $this->sessions->otherSessionCount($user, $request->session()->getId()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['nullable', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->getKey())],
        ], attributes: ['name' => 'نام', 'email' => 'ایمیل']);

        $this->update->handle($user, $data['name'], $data['email'] ?? null);

        return to_route('identity.account')->with('status', 'اطلاعات حساب ذخیره شد.');
    }

    public function signOutOthers(Request $request): RedirectResponse
    {
        $closed = $this->sessions->handle($this->user($request), $request->session()->getId());

        return to_route('identity.account')->with('status', $closed > 0
            ? 'از '.PersianDigits::from($closed).' دستگاه دیگر خارج شدید.'
            : 'دستگاه دیگری وارد نبود؛ ورود خودکار روی دستگاه‌های دیگر هم باطل شد.');
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
