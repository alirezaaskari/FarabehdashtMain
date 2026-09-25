<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Filament\Resources\Articles;

use App\Contracts\ToolDirectory;
use App\Models\User;
use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Actions\SaveArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use App\Modules\Encyclopedia\Filament\Resources\Articles\Pages\CreateArticle;
use App\Modules\Encyclopedia\Filament\Resources\Articles\Pages\EditArticle;
use App\Modules\Encyclopedia\Filament\Resources\Articles\Pages\ListArticles;
use App\Support\Admin\NavigationGroup;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * ویرایشگر محتوای دانشنامه.
 *
 * فرم فقط داده جمع می‌کند؛ ذخیره از {@see SaveArticle}
 * و انتشار از {@see PublishArticle} می‌گذرد، پس
 * قاعده «بدون بازبین منتشر نمی‌شود» از راه پنل هم دور زدنی نیست. حذف وجود
 * ندارد: محتوای منتشرشده نشانی عمومی دارد.
 */
final class ArticleResource extends Resource
{
    public const ABILITY = 'admin.content.review';

    protected static ?string $model = Article::class;

    protected static ?string $slug = 'articles';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 44;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function getNavigationLabel(): string
    {
        return 'مقاله‌ها و راهنماها';
    }

    public static function getModelLabel(): string
    {
        return 'محتوا';
    }

    public static function getPluralModelLabel(): string
    {
        return 'محتواهای دانشنامه';
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can(self::ABILITY) === true;
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('مشخصات')->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')->label('عنوان')->required()->maxLength(255),
                    Select::make('type')
                        ->label('نوع محتوا')
                        ->options(self::typeOptions())
                        ->required()
                        ->native(false),
                ]),
                TextInput::make('slug')
                    ->label('نشانی')
                    ->helperText('فقط حروف کوچک لاتین، عدد و خط تیره؛ پس از انتشار ثابت می‌ماند.')
                    ->prefix('/encyclopedia/')
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->required()
                    ->maxLength(255)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)
                    ->disabled(static fn (?Article $record): bool => $record?->status === ArticleStatus::Published),
                Textarea::make('summary')
                    ->label('خلاصه')
                    ->helperText('زیر عنوان و در نتیجه جست‌وجو دیده می‌شود.')
                    ->required()
                    ->maxLength(500)
                    ->rows(3),
            ]),

            Section::make('بخش‌ها')
                ->description('هر بخش یک عنوان در فهرست «در این مقاله» می‌گیرد. بین پاراگراف‌ها یک خط خالی بگذارید.')
                ->schema([
                    Repeater::make('sections')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('heading')->label('عنوان بخش')->required()->maxLength(255),
                            Textarea::make('body')->label('متن')->required()->rows(8),
                            Grid::make(2)->schema([
                                Textarea::make('note')->label('نکته کلیدی')->rows(2),
                                TextInput::make('tool_slug')
                                    ->label('ابزار درون متن')
                                    ->helperText('شناسه لاتین ابزار، مثل noise-dose')
                                    ->extraInputAttributes(['dir' => 'ltr'])
                                    ->rule(static fn (): Closure => self::existingTool()),
                            ]),
                        ])
                        ->itemLabel(static fn (array $state): ?string => $state['heading'] ?? null)
                        ->addActionLabel('افزودن بخش')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->defaultItems(1)
                        ->minItems(1),
                ]),

            Section::make('منابع')
                ->description('منبع بدون ویرایش یا سال «نسخه‌دار» حساب نمی‌شود و برای انتشار کافی نیست.')
                ->schema([
                    Repeater::make('references')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('title')->label('عنوان منبع')->required()->maxLength(255),
                            Grid::make(4)->schema([
                                TextInput::make('publisher')->label('ناشر')->maxLength(255),
                                TextInput::make('edition')->label('ویرایش')->maxLength(64),
                                TextInput::make('year')->label('سال')->integer()->minValue(1300)->maxValue(2100),
                                TextInput::make('url')->label('نشانی')->url()->maxLength(512)
                                    ->extraInputAttributes(['dir' => 'ltr']),
                            ]),
                        ])
                        ->itemLabel(static fn (array $state): ?string => $state['title'] ?? null)
                        ->addActionLabel('افزودن منبع')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->defaultItems(0),
                ]),

            Section::make('بازبینی علمی')
                ->description('بدون بازبین و تاریخ بازبینی، محتوا منتشر نمی‌شود. موعد بازبینی بعدی هنگام انتشار از نوع محتوا حساب می‌شود.')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('reviewer_id')
                            ->label('بازبین علمی')
                            ->searchable()
                            ->getSearchResultsUsing(static fn (string $search): array => self::searchUsers($search))
                            ->getOptionLabelUsing(static fn (mixed $value): ?string => User::query()->find($value)?->getFilamentName()),
                        DatePicker::make('reviewed_at')->label('تاریخ بازبینی')->maxDate(now()),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('عنوان')->searchable()->wrap(),
                TextColumn::make('type')->label('نوع')
                    ->formatStateUsing(static fn (ArticleType $state): string => $state->label()),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(static fn (ArticleStatus $state): string => $state->label())
                    ->color(static fn (ArticleStatus $state): string => match ($state) {
                        ArticleStatus::Published => 'success',
                        ArticleStatus::InReview => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')->label('آخرین ویرایش')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(self::statusOptions()),
                SelectFilter::make('type')->label('نوع')->options(self::typeOptions()),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }

    /** @return array<string, string> */
    private static function typeOptions(): array
    {
        $options = [];

        foreach (ArticleType::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        $options = [];

        foreach (ArticleStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    /** @return array<int, string> */
    private static function searchUsers(string $search): array
    {
        $options = [];

        $users = User::query()
            ->where('name', 'like', '%'.$search.'%')
            ->orWhere('mobile', 'like', '%'.$search.'%')
            ->limit(20)
            ->get();

        foreach ($users as $user) {
            $options[$user->id] = $user->getFilamentName();
        }

        return $options;
    }

    /**
     * شناسه ابزار باید واقعاً ابزاری باشد. اگر ماژول ابزارها خاموش است، بلوک
     * ابزار اصلاً نمایش داده نمی‌شود و بررسی بی‌معناست.
     */
    private static function existingTool(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || trim($value) === '' || ! app()->bound(ToolDirectory::class)) {
                return;
            }

            if (app(ToolDirectory::class)->find(trim($value)) === null) {
                $fail('ابزاری با این شناسه پیدا نشد.');
            }
        };
    }
}
