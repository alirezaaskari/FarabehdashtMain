<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Exceptions\OtpException;
use App\Modules\Identity\Http\Requests\RequestCodeRequest;
use App\Modules\Identity\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final readonly class LoginController
{
    public const PENDING_MOBILE = 'identity.pending_mobile';

    public function __construct(private OtpService $otp) {}

    public function show(): View
    {
        return view('identity::login');
    }

    public function store(RequestCodeRequest $request): RedirectResponse
    {
        $mobile = $request->mobile();

        try {
            $this->otp->request($mobile->value, OtpPurpose::Login, $request->ip());
        } catch (OtpException $exception) {
            return back()->withInput()->withErrors(['mobile' => $exception->getMessage()]);
        }

        $request->session()->put(self::PENDING_MOBILE, $mobile->value);

        return to_route('identity.verify.show');
    }
}
