<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Filament\Resources\Substances\Pages;

use App\Modules\Chemicals\Filament\Resources\Substances\SubstanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSubstances extends ListRecords
{
    protected static string $resource = SubstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('ماده تازه')];
    }
}
