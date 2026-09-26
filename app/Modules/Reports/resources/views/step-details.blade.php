<x-layouts.workspace art="reports-step-details" :title="'مشخصات — '.($report->title ?? 'گزارش')"
                     heading="مشخصات گزارش"
                     lede="آنچه روی برگه اول گزارش چاپ می‌شود. نام تهیه‌کننده روی صفحه تأیید اصالت هم دیده می‌شود."
                     nav="reports">

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[['گزارش‌ها', route('reports.index')], [$report->title ?? 'گزارش', null]]" />
    </x-slot:breadcrumb>

    @include('reports::partials.steps', ['current' => $step, 'report' => $report])

    <x-card :title="'منبع: '.($source?->label() ?? 'در دسترس نیست')" size="lg">
        <form method="POST" action="{{ route('reports.details.save', $report->uuid) }}" class="grid gap-5 md:grid-cols-2">
            @csrf
            @method('PUT')

            <x-field name="title" label="عنوان گزارش" required class="md:col-span-2"
                     :value="old('title', $report->title)" :error="$errors->first('title')" />
            <x-field name="client_name" label="کارفرما" :value="old('client_name', $report->client_name)"
                     hint="روی صفحه تأیید اصالت نمایش داده نمی‌شود." :error="$errors->first('client_name')" />
            <x-field name="site" label="محل اندازه‌گیری" :value="old('site', $report->site)" :error="$errors->first('site')" />
            <x-field name="measured_on" label="زمان اندازه‌گیری" :value="old('measured_on', $report->measured_on)"
                     hint="مثلاً «مهر ۱۴۰۵» یا «۱۰ تا ۱۲ مهر ۱۴۰۵»." :error="$errors->first('measured_on')" />
            <x-field name="author_name" label="نام تهیه‌کننده" required :value="old('author_name', $report->author_name)"
                     :error="$errors->first('author_name')" />

            <div class="flex flex-col gap-2 md:col-span-2">
                <x-toggle name="include_method" label="بخش «روش محاسبه»"
                          description="فهرست فرمول‌ها و نسخه هرکدام، تا نتیجه‌ها سال‌ها بعد هم بازتولیدپذیر باشند."
                          :checked="(bool) old('include_method', $report->include_method)" />
                <x-toggle name="include_equipment" label="پیوست «تجهیزات به‌کاررفته»"
                          description="مدل، سریال، کلاس دقت و کالیبراسیون هر تجهیز، خودکار از دفترچه تجهیزات."
                          :checked="(bool) old('include_equipment', $report->include_equipment)" />
            </div>

            <div class="md:col-span-2">
                <x-button type="submit" variant="primary" icon="forward">ذخیره و ادامه</x-button>
            </div>
        </form>
    </x-card>

    <form method="POST" action="{{ route('reports.destroy', $report->uuid) }}" class="mt-6">
        @csrf
        @method('DELETE')
        <x-button type="submit" variant="secondary" size="sm">حذف این پیش‌نویس</x-button>
    </form>

</x-layouts.workspace>
