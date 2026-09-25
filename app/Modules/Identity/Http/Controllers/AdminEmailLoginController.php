<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\SignInAdminWithPassword;
use App\Modules\Identity\Domain\Exceptions\AdminSignInFailed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** ورود مدیر با ایمیل و رمز عبور؛ کنار ورود پیامکی، نه به‌جایش. */
final readonly class AdminEmailLoginController
{
    public function __construct(private SignInAdminWithPassword $signIn) {}

    public function show(): View
    {
        return view('identity::admin-email');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate(
            [
                'email' => ['required', 'email:rfc', 'max:190'],
                'password' => ['required', 'string', 'max:200'],
            ],
            attributes: ['email' => 'ایمیل', 'password' => 'رمز عبور'],
        );

        try {
            $this->signIn->handle((string) $input['email'], (string) $input['password'], $request->ip());
        } catch (AdminSignInFailed $exception) {
            return back()->onlyInput('email')->withErrors(['email' => $exception->getMessage()]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('identity.profiles'));
    }
}
