<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Filament\Resources\Substances\Pages;

use App\Modules\Chemicals\Actions\PublishSubstance;
use App\Modules\Chemicals\Actions\SaveSubstance;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\ExposureLimit;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Domain\SubstanceFact;
use App\Modules\Chemicals\Filament\Resources\Substances\SubstanceResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use RuntimeException;

final class EditSubstance extends EditRecord
{
    protected static string $resource = SubstanceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $substance = $this->substance()->load(['synonyms', 'limits', 'facts']);

        $data['synonyms'] = $substance->synonyms->pluck('name')->all();
        $data['limits'] = array_map(static fn (ExposureLimit $limit): array => [
            'authority' => $limit->authority->value,
            'type' => $limit->type->value,
            'value' => $limit->value,
            'unit' => $limit->unit,
            'note' => $limit->note,
            'reference_title' => $limit->reference_title,
            'reference_edition' => $limit->reference_edition,
            'reference_year' => $limit->reference_year,
        ], $substance->orderedLimits());

        foreach (SaveSubstance::FACT_FIELDS as $field => $kind) {
            $data[$field] = $substance->factsOf($kind)->map(static fn (SubstanceFact $fact): string => $fact->text)->all();
        }

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Substance);

        return app(SaveSubstance::class)->handle($record, $data, $this->actorId());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('دیدن در سایت')
                ->color('gray')
                ->url(fn (): string => route('chemicals.show', $this->substance()->slug), shouldOpenInNewTab: true)
                ->visible(fn (): bool => $this->substance()->status === SubstanceStatus::Published && Route::has('chemicals.show')),

            Action::make('publish')
                ->label('انتشار')
                ->requiresConfirmation()
                ->modalDescription('نسخه ذخیره‌شده منتشر می‌شود؛ اگر تغییری ذخیره نکرده‌اید، اول ذخیره کنید.')
                ->visible(fn (): bool => $this->substance()->status === SubstanceStatus::Draft)
                ->action(function (PublishSubstance $publish): void {
                    try {
                        $this->record = $publish->handle($this->substance(), $this->actorId());

                        Notification::make()->title('منتشر شد: '.$this->substance()->name_fa)->success()->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()->title('منتشر نشد')->body($exception->getMessage())->danger()->send();
                    }

                    $this->fillForm();
                }),
        ];
    }

    private function substance(): Substance
    {
        $record = $this->getRecord();
        assert($record instanceof Substance);

        return $record;
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
