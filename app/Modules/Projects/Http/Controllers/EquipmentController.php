<?php

declare(strict_types=1);

namespace App\Modules\Projects\Http\Controllers;

use App\Models\User;
use App\Modules\Projects\Domain\Equipment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * دفترچه تجهیزات کاربر.
 */
final readonly class EquipmentController
{
    public function index(Request $request): View
    {
        return view('projects::equipment', [
            'equipment' => Equipment::query()
                ->forUser((int) $this->user($request)->getKey())
                ->orderBy('calibration_valid_until')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'accuracy_class' => ['nullable', 'string', 'max:255'],
            'calibrated_on' => ['nullable', 'date'],
            'calibration_valid_until' => ['nullable', 'date', 'after_or_equal:calibrated_on'],
            'calibration_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Equipment::query()->create([...$data, 'user_id' => $this->user($request)->getKey()]);

        return redirect()
            ->route('projects.equipment.index')
            ->with('status', 'تجهیز ثبت شد.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        assert($user instanceof User);

        return $user;
    }
}
