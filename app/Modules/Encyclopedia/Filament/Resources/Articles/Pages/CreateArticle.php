<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Filament\Resources\Articles\Pages;

use App\Modules\Encyclopedia\Actions\SaveArticle;
use App\Modules\Encyclopedia\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    /** @param  array<string, mixed>  $data */
    protected function handleRecordCreation(array $data): Model
    {
        $id = Auth::id();

        return app(SaveArticle::class)->handle(null, $data, is_int($id) ? $id : null);
    }

    protected function getRedirectUrl(): string
    {
        return ArticleResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
