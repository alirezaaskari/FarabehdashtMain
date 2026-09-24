<?php

declare(strict_types=1);

namespace App\Modules\Linking\Filament\Pages;

use App\Modules\Linking\Actions\BlockLink;
use App\Modules\Linking\Actions\RebuildLinks;
use App\Modules\Linking\Domain\LinkBlock;
use App\Modules\Linking\Services\LinkReport;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * گزارش و کنترل پیوندهای داخلی خودکار.
 *
 * پیوندها تک‌به‌تک تأیید نمی‌شوند (DEC-32)؛ مدیر به‌جایش عبارت، مقصد یا جفت
 * مبدأ‌-مقصدی را که غلط پیوند خورده مسدود می‌کند. هر تغییر بلافاصله
 * بازسازی را اجرا می‌کند تا اثرش همین حالا روی سایت دیده شود.
 */
final class InternalLinksPage extends Page
{
    public const ABILITY = 'admin.links.manage';

    protected static ?string $slug = 'internal-links';

    protected static ?int $navigationSort = 60;

    protected string $view = 'linking::filament.pages.internal-links';

    public string $phrase = '';

    public string $sourceKey = '';

    public string $targetKey = '';

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'پیوندهای داخلی';
    }

    public function getTitle(): string
    {
        return 'پیوندهای داخلی';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function report(): LinkReport
    {
        return app(LinkReport::class);
    }

    public function rebuild(RebuildLinks $rebuild): void
    {
        $result = $rebuild->handle();

        Notification::make()
            ->title(sprintf('بازسازی شد: %d پیوند در %d سند', $result['links'], $result['documents']))
            ->success()
            ->send();
    }

    public function block(BlockLink $block, RebuildLinks $rebuild): void
    {
        $this->error = null;

        try {
            $block->handle($this->phrase, $this->sourceKey, $this->targetKey, $this->actorId());
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->reset(['phrase', 'sourceKey', 'targetKey']);
        $rebuild->handle();

        Notification::make()->title('مسدود شد و پیوندها بازسازی شدند')->success()->send();
    }

    public function unblock(int $blockId, RebuildLinks $rebuild): void
    {
        LinkBlock::query()->whereKey($blockId)->delete();
        $rebuild->handle();

        Notification::make()->title('قاعده برداشته شد و پیوندها بازسازی شدند')->success()->send();
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
