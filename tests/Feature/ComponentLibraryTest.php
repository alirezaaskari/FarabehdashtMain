<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class ComponentLibraryTest extends TestCase
{
    public function test_a_disabled_button_is_really_disabled(): void
    {
        $html = Blade::render('<x-button disabled>ذخیره</x-button>');

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('<button', $html);
    }

    public function test_a_button_with_href_renders_a_real_link(): void
    {
        $html = Blade::render('<x-button href="/tools">ابزارها</x-button>');

        $this->assertStringContainsString('<a href="/tools"', $html);
    }

    public function test_a_field_always_has_a_real_label_bound_to_its_input(): void
    {
        $html = Blade::render('<x-field name="tnw" label="دمای تر طبیعی" />');

        $this->assertStringContainsString('<label for="tnw"', $html);
        $this->assertStringContainsString('id="tnw"', $html);
    }

    public function test_a_field_error_is_not_conveyed_by_colour_alone(): void
    {
        $html = Blade::render('<x-field name="tg" label="دمای گویسان" error="مقدار باید عددی باشد." />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="tg-error"', $html);
        $this->assertStringContainsString('مقدار باید عددی باشد.', $html);
        $this->assertStringContainsString('<svg', $html, 'خطا باید آیکون هم داشته باشد، نه فقط رنگ.');
    }

    public function test_numeric_values_are_isolated_left_to_right(): void
    {
        $html = Blade::render('<x-stat label="تراز" value="87.5" unit="dB" />');

        $this->assertStringContainsString('data-numeric', $html);
    }

    public function test_icons_are_hidden_from_screen_readers_by_default(): void
    {
        $html = Blade::render('<x-icon name="check" />');

        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_an_empty_state_offers_a_next_step(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-empty-state title="هنوز فایلی نخریده‌اید" description="فروشگاه را ببینید.">
                <x-slot:action><x-button href="/market">دیدن فروشگاه</x-button></x-slot:action>
            </x-empty-state>
        BLADE);

        $this->assertStringContainsString('هنوز فایلی نخریده‌اید', $html);
        $this->assertStringContainsString('href="/market"', $html);
    }

    public function test_alerts_announce_themselves_appropriately(): void
    {
        $this->assertStringContainsString('role="alert"', Blade::render('<x-alert tone="error">خطا</x-alert>'));
        $this->assertStringContainsString('role="status"', Blade::render('<x-alert tone="success">انجام شد</x-alert>'));
    }

    public function test_a_toggle_is_a_real_checkbox(): void
    {
        $html = Blade::render('<x-toggle name="resume_bank" label="حضور در بانک رزومه" />');

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('<label for="resume_bank"', $html);
    }
}
