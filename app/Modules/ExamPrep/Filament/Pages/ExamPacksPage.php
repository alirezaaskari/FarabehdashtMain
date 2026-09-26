<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Filament\Pages;

use App\Modules\ExamPrep\Actions\ChangePackStatus;
use App\Modules\ExamPrep\Actions\ImportQuestions;
use App\Modules\ExamPrep\Actions\SavePack;
use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Services\QuestionCsv;
use App\Support\Admin\NavigationGroup;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * بسته‌های آمادگی آزمون: ساخت، قیمت، انتشار و ورود سؤال از CSV.
 *
 * ورود سؤال همه یا هیچ است: پیش‌نمایش همان ورود را در تراکنشی برگشت‌خورده
 * اجرا می‌کند، پس خطایی که پیش‌نمایش نشان ندهد در اجرا هم پیش نمی‌آید.
 */
final class ExamPacksPage extends Page
{
    use WithFileUploads;

    public const ABILITY = 'admin.content.publish';

    protected static ?string $slug = 'exam-packs';

    protected static ?int $navigationSort = 47;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected string $view = 'exam_prep::filament.pages.exam-packs';

    /** @var array{title: string, slug: string, exam_name: string, description: string, price: string, exam_question_count: string, exam_minutes: string, topics: string} */
    public array $form = self::BLANK;

    public ?int $editingId = null;

    public ?int $importPackId = null;

    public ?UploadedFile $file = null;

    /** @var array{imported: int, errors: list<array{line: int, error: string}>}|null */
    public ?array $preview = null;

    /** @var list<array{id: int, title: string, slug: string, status: string, price: string, published: int, pending: int, url: string|null}> */
    public array $packs = [];

    private const array BLANK = [
        'title' => '',
        'slug' => '',
        'exam_name' => '',
        'description' => '',
        'price' => '',
        'exam_question_count' => '50',
        'exam_minutes' => '60',
        'topics' => '',
    ];

    public static function getNavigationLabel(): string
    {
        return 'بسته‌های آزمون';
    }

    public function getTitle(): string
    {
        return 'بسته‌های آمادگی آزمون';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function edit(int $id): void
    {
        $pack = ExamPack::query()->with('topics')->findOrFail($id);

        $this->editingId = $pack->id;
        $this->form = [
            'title' => $pack->title,
            'slug' => $pack->slug,
            'exam_name' => $pack->exam_name,
            'description' => $pack->description,
            'price' => (string) $pack->price_toman,
            'exam_question_count' => (string) $pack->exam_question_count,
            'exam_minutes' => (string) $pack->exam_minutes,
            'topics' => $pack->topics->sortBy('sort')->pluck('title')->implode("\n"),
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->form = self::BLANK;
    }

    public function save(SavePack $save): void
    {
        $pack = $this->editingId !== null ? ExamPack::query()->findOrFail($this->editingId) : null;

        try {
            $save->handle($this->form, $this->actorId(), $pack);
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('ذخیره نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($pack === null ? 'بسته ساخته شد (پیش‌نویس)' : 'بسته ذخیره شد')->success()->send();
        $this->cancelEdit();
        $this->load();
    }

    public function publish(int $id, ChangePackStatus $status): void
    {
        $this->change(fn () => $status->publish(ExamPack::query()->findOrFail($id), $this->actorId()), 'بسته منتشر شد');
    }

    public function retire(int $id, ChangePackStatus $status): void
    {
        $this->change(fn () => $status->retire(ExamPack::query()->findOrFail($id), $this->actorId()), 'بسته از فروش برداشته شد');
    }

    public function template(QuestionCsv $csv): StreamedResponse
    {
        $contents = $csv->template();

        return response()->streamDownload(
            static fn () => print $contents,
            'exam-questions-template.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function previewImport(ImportQuestions $import): void
    {
        $this->preview = $this->runImport($import, dryRun: true);
    }

    public function applyImport(ImportQuestions $import): void
    {
        $result = $this->runImport($import, dryRun: false);

        if ($result === null) {
            return;
        }

        if ($result['errors'] !== []) {
            $this->preview = $result;

            return;
        }

        Notification::make()->title(sprintf('%d سؤال وارد و منتشر شد', $result['imported']))->success()->send();
        $this->file = null;
        $this->preview = null;
        $this->load();
    }

    /** @return array{imported: int, errors: list<array{line: int, error: string}>}|null */
    private function runImport(ImportQuestions $import, bool $dryRun): ?array
    {
        if ($this->file === null || $this->importPackId === null) {
            Notification::make()->title('بسته و فایل را انتخاب کنید')->danger()->send();

            return null;
        }

        $pack = ExamPack::query()->findOrFail($this->importPackId);

        try {
            return $import->handle($pack, (string) file_get_contents($this->file->getRealPath()), $this->actorId(), $dryRun);
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('فایل خوانده نشد')->body($exception->getMessage())->danger()->send();

            return null;
        }
    }

    private function change(callable $action, string $done): void
    {
        try {
            $action();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($done)->success()->send();
        $this->load();
    }

    private function actorId(): int
    {
        return (int) Auth::id();
    }

    private function load(): void
    {
        $this->packs = ExamPack::query()
            ->withCount([
                'questions as published_count' => static fn ($q) => $q->where('status', QuestionStatus::Published),
                'questions as pending_count' => static fn ($q) => $q->where('status', QuestionStatus::Pending),
            ])
            ->orderBy('title')
            ->get()
            ->map(static fn (ExamPack $pack): array => [
                'id' => $pack->id,
                'title' => $pack->title,
                'slug' => $pack->slug,
                'status' => $pack->status->label(),
                'price' => $pack->price()->format(),
                'published' => (int) $pack->getAttribute('published_count'),
                'pending' => (int) $pack->getAttribute('pending_count'),
                'url' => $pack->isPublished() ? route('exam_prep.show', $pack->slug) : null,
            ])
            ->values()
            ->all();
    }
}
