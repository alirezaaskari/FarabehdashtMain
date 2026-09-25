<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Filament\Resources\Substances\Pages;

use App\Modules\Chemicals\Actions\SaveSubstance;
use App\Modules\Chemicals\Filament\Resources\Substances\SubstanceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class CreateSubstance extends CreateRecord
{
    protected static string $resource = SubstanceResource::class;

    /** @param  array<string, mixed>  $data */
    protected function handleRecordCreation(array $data): Model
    {
        $id = Auth::id();

        return app(SaveSubstance::class)->handle(null, $data, is_int($id) ? $id : null);
    }

    protected function getRedirectUrl(): string
    {
        return SubstanceResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
