<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Filament\Resources\Substances;

use App\Modules\Chemicals\Actions\PublishSubstance;
use App\Modules\Chemicals\Actions\SaveSubstance;
use App\Modules\Chemicals\Domain\CasNumber;
use App\Modules\Chemicals\Domain\Enums\LimitAuthority;
use App\Modules\Chemicals\Domain\Enums\LimitType;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Filament\Resources\Substances\Pages\CreateSubstance;
use App\Modules\Chemicals\Filament\Resources\Substances\Pages\EditSubstance;
use App\Modules\Chemicals\Filament\Resources\Substances\Pages\ListSubstances;
use App\Support\Admin\NavigationGroup;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
 * ویرایشگر بانک مواد.
 *
 * ذخیره از {@see SaveSubstance} و انتشار از {@see PublishSubstance} می‌گذرد،
 * پس قاعده «حد مواجهه بدون منبع منتشر نمی‌شود» از راه پنل هم دور زدنی نیست.
 * ورود CSV همچنان برای داده پایه دسته‌ای است؛ حد مواجهه و منبعش این‌جا
 * وارد می‌شود.
 */
final class SubstanceResource extends Resource
{
    public const ABILITY = 'admin.chemicals.manage';

    protected static ?string $model = Substance::class;

    protected static ?string $slug = 'substances';

    protected static ?string $recordTitleAttribute = 'name_fa';

    protected static ?int $navigationSort = 45;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    public static function getNavigationLabel(): string
    {
        return 'بانک مواد';
    }

    public static function getModelLabel(): string
    {
        return 'ماده';
    }

    public static function getPluralModelLabel(): string
    {
        return 'مواد شیمیایی';
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
            Section::make('شناسه ماده')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name_fa')->label('نام فارسی')->required()->maxLength(255),
                    TextInput::make('name_en')->label('نام انگلیسی')->required()->maxLength(255)
                        ->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('cas_number')->label('شماره CAS')->required()->maxLength(32)
                        ->placeholder('108-88-3')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->unique(ignoreRecord: true)
                        ->rule(static fn (): Closure => self::validCas()),
                    TextInput::make('slug')->label('نشانی')->required()->maxLength(255)
                        ->prefix('/chemicals/')
                        ->helperText('فقط حروف کوچک لاتین، عدد و خط تیره؛ پس از انتشار ثابت می‌ماند.')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->unique(ignoreRecord: true)
                        ->disabled(static fn (?Substance $record): bool => $record?->status === SubstanceStatus::Published),
                ]),
                TagsInput::make('synonyms')->label('نام‌های دیگر')
                    ->helperText('در جست‌وجو پیدا می‌شوند؛ هر نام را با Enter جدا کنید.'),
            ]),

            Section::make('مشخصات')->collapsible()->schema([
                Grid::make(3)->schema([
                    TextInput::make('formula')->label('فرمول')->maxLength(64)->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('molar_mass')->label('وزن مولکولی (g/mol)')->numeric()->minValue(0)
                        ->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('physical_state')->label('حالت فیزیکی')->maxLength(64),
                ]),
                Textarea::make('description')->label('توضیح')->rows(4),
            ]),

            Section::make('حدود مواجهه')
                ->description('هر حد باید منبع با ویرایش یا سال داشته باشد؛ یک حد بی‌منبع کل ماده را از انتشار بازمی‌دارد. هر مرجع برای هر نوع حد فقط یک مقدار دارد.')
                ->schema([
                    Repeater::make('limits')
                        ->hiddenLabel()
                        ->schema([
                            Grid::make(4)->schema([
                                Select::make('authority')->label('مرجع')->required()->native(false)
                                    ->options(self::options(LimitAuthority::cases())),
                                Select::make('type')->label('نوع حد')->required()->native(false)
                                    ->options(self::options(LimitType::cases())),
                                TextInput::make('value')->label('مقدار')->required()->numeric()->minValue(0)
                                    ->extraInputAttributes(['dir' => 'ltr']),
                                TextInput::make('unit')->label('واحد')->required()->maxLength(32)
                                    ->datalist(['ppm', 'mg/m³', 'f/cc'])
                                    ->extraInputAttributes(['dir' => 'ltr']),
                            ]),
                            Grid::make(3)->schema([
                                TextInput::make('reference_title')->label('منبع')->maxLength(255)
                                    ->placeholder('ACGIH TLVs and BEIs'),
                                TextInput::make('reference_edition')->label('ویرایش')->maxLength(64),
                                TextInput::make('reference_year')->label('سال')->integer()->minValue(1300)->maxValue(2100),
                            ]),
                            TextInput::make('note')->label('یادداشت')->maxLength(255)
                                ->placeholder('مثلاً: جذب پوستی'),
                        ])
                        ->itemLabel(static fn (array $state): ?string => self::limitLabel($state))
                        ->addActionLabel('افزودن حد مواجهه')
                        ->collapsible()
                        ->defaultItems(0)
                        ->rule(static fn (): Closure => self::distinctLimits()),
                ]),

            Section::make('مسیر، علائم و حفاظت')->collapsible()->schema([
                self::factList('routes', 'مسیرهای مواجهه'),
                self::factList('symptoms', 'علائم و اثرات'),
                self::factList('protection', 'حفاظت فردی'),
            ]),

            Section::make('نمونه‌برداری و آنالیز')->collapsible()->collapsed()->schema([
                Grid::make(2)->schema([
                    TextInput::make('sampling_media')->label('محیط نمونه‌برداری')->maxLength(255),
                    TextInput::make('sampling_flow')->label('دبی نمونه‌برداری')->maxLength(255),
                    TextInput::make('analysis_method')->label('روش آنالیز')->maxLength(255),
                    TextInput::make('method_number')->label('شماره روش')->maxLength(64)
                        ->extraInputAttributes(['dir' => 'ltr']),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_fa')->label('نام')->searchable()
                    ->description(static fn (Substance $record): string => $record->name_en),
                TextColumn::make('cas_number')->label('CAS')->searchable(),
                TextColumn::make('limits_count')->label('حد مواجهه')->counts('limits'),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(static fn (SubstanceStatus $state): string => $state->label())
                    ->color(static fn (SubstanceStatus $state): string => $state === SubstanceStatus::Published ? 'success' : 'gray'),
                TextColumn::make('updated_at')->label('آخرین ویرایش')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(self::options(SubstanceStatus::cases())),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubstances::route('/'),
            'create' => CreateSubstance::route('/create'),
            'edit' => EditSubstance::route('/{record}/edit'),
        ];
    }

    private static function factList(string $field, string $label): Repeater
    {
        return Repeater::make($field)
            ->label($label)
            ->simple(TextInput::make('text')->required()->maxLength(255))
            ->addActionLabel('افزودن')
            ->reorderableWithButtons()
            ->defaultItems(0);
    }

    /**
     * @param  list<LimitAuthority|LimitType|SubstanceStatus>  $cases
     * @return array<string, string>
     */
    private static function options(array $cases): array
    {
        $options = [];

        foreach ($cases as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** @param  array<string, mixed>  $state */
    private static function limitLabel(array $state): ?string
    {
        $authority = LimitAuthority::tryFrom((string) ($state['authority'] ?? ''));
        $type = LimitType::tryFrom((string) ($state['type'] ?? ''));

        if ($authority === null || $type === null) {
            return null;
        }

        return $authority->label().' · '.$type->shortLabel();
    }

    private static function validCas(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && ! CasNumber::isValid($value)) {
                $fail('شماره CAS نادرست است یا رقم کنترلی‌اش نمی‌خواند (قالب درست: 108-88-3).');
            }
        };
    }

    /** دو ردیف با یک مرجع و یک نوع، قید یکتای جدول را می‌شکند و معنایش هم مبهم است. */
    private static function distinctLimits(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            $seen = [];

            foreach (is_array($value) ? $value : [] as $limit) {
                $key = ($limit['authority'] ?? '').'|'.($limit['type'] ?? '');

                if (isset($seen[$key])) {
                    $fail('برای هر مرجع و هر نوع حد فقط یک ردیف مجاز است.');

                    return;
                }

                $seen[$key] = true;
            }
        };
    }
}
