<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Filament\Pages;

use App\Modules\Chemicals\Actions\ApplyCsvImport;
use App\Modules\Chemicals\Domain\Import\ImportAction;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Services\CsvExporter;
use App\Modules\Chemicals\Services\CsvImporter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\WithFileUploads;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ورود و خروج دسته‌ای بانک مواد با CSV.
 *
 * معیار پذیرش این بخش: «ورود CSV قبل از اجرا گزارش تغییرات می‌دهد.» این صفحه
 * همان دو گام را دقیقاً روی هم می‌گذارد: «پیش‌نمایش» فقط می‌خواند و برنامه
 * را در `$summary` (آرایه‌های ساده، قابل نگه‌داشتن بین درخواست‌های Livewire)
 * ذخیره می‌کند؛ «اجرا» فایل را دوباره از همان مسیر موقت می‌خواند و برنامه را
 * از نو می‌سازد — پنجره کوتاهی بین این دو وجود دارد که در آن پایگاه داده
 * می‌تواند تغییر کند، ولی چون کلید تطبیق CAS است و اکشن در یک تراکنش
 * می‌نویسد، نتیجه باز هم منسجم می‌ماند.
 *
 * چرا خودِ `ImportPlan` بین دو درخواست نگه داشته نمی‌شود: DTOهای این ماژول
 * `Livewire\Wireable` را پیاده نمی‌کنند و اضافه‌کردنش فقط برای این یک صفحه
 * پیچیدگی‌ای است که ارزشش را ندارد؛ فایل موقت Livewire خودش قابل‌اعتماد بین
 * درخواست‌ها می‌ماند.
 */
final class ChemicalImportPage extends Page
{
    use WithFileUploads;

    public const ABILITY = 'admin.chemicals.manage';

    protected static ?string $slug = 'chemicals-import';

    protected static ?int $navigationSort = 46;

    protected string $view = 'chemicals::filament.pages.import';

    public ?UploadedFile $file = null;

    /** @var array<string, mixed>|null */
    public ?array $summary = null;

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'ورود CSV مواد';
    }

    public function getTitle(): string
    {
        return 'ورود و خروج CSV بانک مواد';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function preview(CsvImporter $importer): void
    {
        $this->error = null;
        $this->summary = null;

        if ($this->file === null) {
            $this->error = 'فایلی انتخاب نشده است.';

            return;
        }

        try {
            $plan = $importer->plan((string) file_get_contents($this->file->getRealPath()));
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->summary = [
            'counts' => [
                'create' => $plan->count(ImportAction::Create),
                'update' => $plan->count(ImportAction::Update),
                'unchanged' => $plan->count(ImportAction::Unchanged),
                'invalid' => $plan->count(ImportAction::Invalid),
            ],
            'invalidRows' => array_map(
                static fn ($row): array => ['line' => $row->line, 'reason' => $row->reason],
                $plan->of(ImportAction::Invalid),
            ),
            'updateRows' => array_map(
                static fn ($row): array => [
                    'cas' => $row->data['cas_number'],
                    'name' => $row->data['name_fa'],
                    'changes' => array_map(
                        static fn ($c): string => sprintf('%s: «%s» ← «%s»', $c->fieldLabel, $c->after ?? '—', $c->before ?? '—'),
                        $row->changes,
                    ),
                ],
                $plan->of(ImportAction::Update),
            ),
            'createRows' => array_map(
                static fn ($row): array => ['cas' => $row->data['cas_number'], 'name' => $row->data['name_fa']],
                $plan->of(ImportAction::Create),
            ),
            'canApply' => $plan->hasWritableChanges(),
        ];
    }

    public function apply(CsvImporter $importer, ApplyCsvImport $applier): void
    {
        if ($this->file === null || $this->summary === null) {
            return;
        }

        try {
            // فایل دوباره خوانده می‌شود، نه اینکه شیء ImportPlan نگه داشته
            // شده باشد — دلیلش در توضیح بالای کلاس آمده.
            $plan = $importer->plan((string) file_get_contents($this->file->getRealPath()));
            $applier->handle($plan, $this->actorId());

            Notification::make()
                ->title(sprintf(
                    'اجرا شد: %d ماده تازه، %d به‌روزرسانی',
                    $plan->count(ImportAction::Create),
                    $plan->count(ImportAction::Update),
                ))
                ->success()
                ->send();

            $this->file = null;
            $this->summary = null;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Notification::make()->title('اجرا نشد')->body($exception->getMessage())->danger()->send();
        }
    }

    public function export(CsvExporter $exporter): StreamedResponse
    {
        $csv = $exporter->export(Substance::query()->orderBy('name_fa')->get());

        return response()->streamDownload(
            static fn () => print $csv,
            'substances-'.now()->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
