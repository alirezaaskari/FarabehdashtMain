<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Filament\Resources\Articles\Pages;

use App\Modules\Encyclopedia\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('محتوای تازه')];
    }
}
