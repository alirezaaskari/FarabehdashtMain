<x-filament-panels::page>

    <x-filament::section heading="راهنمای ورود از اکسل" collapsible>
        <div class="flex flex-col gap-4 text-sm leading-7 text-gray-700 dark:text-gray-300">
            <ol class="flex list-decimal flex-col gap-2 ps-5">
                <li>
                    <strong>قالب را بگیرید.</strong> دکمه «دریافت قالب اکسل» یک فایل با سربرگ درست و دو ردیف
                    نمونه (تولوئن و آمونیاک) می‌دهد. آن را در اکسل باز کنید. اگر می‌خواهید مواد فعلی را ویرایش
                    کنید، به‌جایش «خروجی بانک فعلی» را بگیرید.
                </li>
                <li>
                    <strong>هر ماده یک ردیف.</strong> ردیف اول (نام ستون‌ها) را تغییر ندهید و ستونی جابه‌جا
                    یا اضافه نکنید. ردیف‌های نمونه را اگر نمی‌خواهید پاک کنید.
                </li>
                <li>
                    <strong>ذخیره به‌صورت CSV.</strong> در اکسل: File ← Save As ← نوع فایل
                    <span dir="ltr">«CSV UTF-8 (Comma delimited) (*.csv)»</span>. نوع «Excel Workbook (xlsx)»
                    پذیرفته نمی‌شود و بدون UTF-8 نام‌های فارسی خراب می‌شوند. در Google Sheets:
                    <span dir="ltr">File → Download → CSV</span>.
                </li>
                <li>
                    <strong>بارگذاری و پیش‌نمایش.</strong> فایل را انتخاب و «پیش‌نمایش تغییرات» را بزنید.
                    هنوز چیزی ذخیره نشده؛ صفحه می‌گوید کدام ماده تازه است، کدام عوض می‌شود (با مقدار قبل و بعد)
                    و کدام ردیف ایراد دارد و چرا.
                </li>
                <li>
                    <strong>اجرا.</strong> اگر پیش‌نمایش درست بود «اجرا و نوشتن روی بانک» را بزنید. ردیف‌های
                    نامعتبر نوشته نمی‌شوند؛ اصلاحشان کنید و فایل را دوباره بفرستید.
                </li>
                <li>
                    <strong>تکمیل و انتشار.</strong> ماده تازه همیشه پیش‌نویس است، چون فایل حد مواجهه و منبع
                    ندارد. از «مواد شیمیایی» در همین پنل ماده را باز کنید، حد مواجهه با مرجع و سال، راه‌های
                    ورود، علائم و حفاظت را اضافه کنید و «انتشار» را بزنید.
                </li>
            </ol>

            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 pe-3 text-start">ستون</th>
                            <th class="py-2 pe-3 text-start">یعنی</th>
                            <th class="py-2 pe-3 text-start">الزامی</th>
                            <th class="py-2 text-start">نمونه</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            ['cas_number', 'شماره CAS؛ کلید تشخیص ماده. رقم کنترلی بررسی می‌شود.', 'بله', '108-88-3'],
                            ['name_fa', 'نام فارسی', 'بله', 'تولوئن'],
                            ['name_en', 'نام انگلیسی', 'بله', 'Toluene'],
                            ['formula', 'فرمول شیمیایی', 'خیر', 'C7H8'],
                            ['molar_mass', 'جرم مولکولی (g/mol)؛ عدد مثبت، ارقام فارسی هم قبول است', 'خیر', '92.14'],
                            ['physical_state', 'حالت فیزیکی', 'خیر', 'مایع / گاز / جامد'],
                        ] as [$column, $meaning, $required, $example])
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pe-3"><code dir="ltr">{{ $column }}</code></td>
                                <td class="py-2 pe-3">{{ $meaning }}</td>
                                <td class="py-2 pe-3">{{ $required }}</td>
                                <td class="py-2">{{ $example }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                ماده‌ای که CAS آن در بانک هست به‌روز می‌شود و بقیه تازه ساخته می‌شوند؛ هیچ ماده‌ای با این صفحه
                حذف نمی‌شود. هر بار حداکثر @fa((int) config('chemicals.csv.max_rows', 500)) ردیف. ویرگول یا
                نقطه‌ویرگول هر دو به‌عنوان جداکننده پذیرفته می‌شوند.
            </p>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <x-filament::button wire:click="template" icon="heroicon-o-arrow-down-tray">دریافت قالب اکسل</x-filament::button>
            <x-filament::button color="gray" wire:click="export">خروجی بانک فعلی</x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section>
        <div class="flex flex-col gap-4">
            <input type="file" wire:model="file" accept=".csv,text/csv"
                   class="block w-full text-sm text-gray-700 dark:text-gray-300">

            <div wire:loading wire:target="file" class="text-sm text-gray-500">در حال بارگذاری فایل…</div>

            <div class="flex gap-2">
                <x-filament::button wire:click="preview" :disabled="! $file">پیش‌نمایش تغییرات</x-filament::button>

                @if ($summary && $summary['canApply'])
                    <x-filament::button color="success" wire:click="apply">
                        اجرا و نوشتن روی بانک
                    </x-filament::button>
                @endif
            </div>

            @if ($error)
                <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $error }}</p>
            @endif
        </div>
    </x-filament::section>

    @if ($summary)
        <x-filament::section>
            <div class="grid grid-cols-4 gap-4 text-center">
                <div>
                    <span class="block text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['counts']['create'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">ماده تازه</span>
                </div>
                <div>
                    <span class="block text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['counts']['update'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">به‌روزرسانی</span>
                </div>
                <div>
                    <span class="block text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['counts']['unchanged'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">بدون تغییر</span>
                </div>
                <div>
                    <span class="block text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $summary['counts']['invalid'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">نامعتبر</span>
                </div>
            </div>

            @if ($summary['invalidRows'] !== [])
                <div class="mt-5">
                    <h3 class="text-sm font-bold text-danger-600 dark:text-danger-400">ردیف‌های نامعتبر</h3>
                    <ul class="mt-2 flex flex-col gap-1 text-xs text-gray-600 dark:text-gray-300">
                        @foreach ($summary['invalidRows'] as $row)
                            <li>خط {{ $row['line'] }}: {{ $row['reason'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($summary['updateRows'] !== [])
                <div class="mt-5">
                    <h3 class="text-sm font-bold text-gray-950 dark:text-white">به‌روزرسانی‌ها</h3>
                    <ul class="mt-2 flex flex-col gap-2 text-xs text-gray-600 dark:text-gray-300">
                        @foreach ($summary['updateRows'] as $row)
                            <li>
                                <span dir="ltr" class="font-semibold">{{ $row['cas'] }}</span>
                                — {{ $row['name'] }}:
                                {{ implode('، ', $row['changes']) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($summary['createRows'] !== [])
                <div class="mt-5">
                    <h3 class="text-sm font-bold text-gray-950 dark:text-white">مواد تازه</h3>
                    <ul class="mt-2 flex flex-col gap-1 text-xs text-gray-600 dark:text-gray-300">
                        @foreach ($summary['createRows'] as $row)
                            <li><span dir="ltr" class="font-semibold">{{ $row['cas'] }}</span> — {{ $row['name'] }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        ماده تازه همیشه پیش‌نویس می‌ماند؛ برای انتشار باید حد مواجهه با منبع نسخه‌دار اضافه شود.
                    </p>
                </div>
            @endif
        </x-filament::section>
    @endif

</x-filament-panels::page>
