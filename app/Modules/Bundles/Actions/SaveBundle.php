<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Actions;

use App\Modules\Bundles\Domain\Bundle;
use App\Modules\Bundles\Domain\BundleItem;
use App\Modules\Bundles\Domain\Enums\BundleStatus;
use App\Modules\Bundles\Events\BundleSaved;
use App\Modules\Bundles\Services\ComponentCatalog;
use App\Support\Money;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ساخت یا ویرایش بسته از پنل.
 *
 * DEC-47: قیمت بسته باید کمتر از جمع قیمت تکی اجزا باشد؛ وگرنه بسته چیزی
 * برای خریدار ندارد. دست‌کم دو جزء، و هر جزء باید همین حالا فروختنی باشد.
 */
final readonly class SaveBundle
{
    public const int MIN_ITEMS = 2;

    public function __construct(
        private ComponentCatalog $catalog,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    /**
     * @param  array{title: string, slug: string, description: string, price: string, items: list<string>}  $input
     *                                                                                                              items: «نوع:شناسه»
     */
    public function handle(array $input, int $actorId, ?Bundle $bundle = null): Bundle
    {
        $title = trim($input['title']);
        $description = trim($input['description']);
        $slug = Str::lower(trim($input['slug']));
        $price = Money::fromInput($input['price']);
        $keys = array_values(array_unique(array_filter(array_map('trim', $input['items']))));

        if (mb_strlen($title) < 3 || mb_strlen($description) < 10) {
            throw new InvalidArgumentException('عنوان و معرفی بسته را کامل بنویسید.');
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new InvalidArgumentException('نشانی بسته فقط حروف کوچک لاتین، عدد و خط تیره است؛ مثل factory-noise.');
        }

        if (Bundle::query()->where('slug', $slug)->when($bundle, fn ($q) => $q->whereKeyNot($bundle->id))->exists()) {
            throw new InvalidArgumentException('این نشانی را بسته دیگری دارد.');
        }

        if (count($keys) < self::MIN_ITEMS) {
            throw new InvalidArgumentException('بسته دست‌کم دو جزء دارد.');
        }

        $sum = Money::zero();

        foreach ($keys as $key) {
            [$kind, $ref] = array_pad(explode(':', $key, 2), 2, '');
            $component = $this->catalog->find($kind, $ref)
                ?? throw new InvalidArgumentException('جزء «'.$key.'» پیدا نشد یا دیگر فروختنی نیست.');
            $sum = $sum->plus($component->listPrice);
        }

        if ($price->isZero() || ! $price->isLessThan($sum)) {
            throw new InvalidArgumentException('قیمت بسته باید بیشتر از صفر و کمتر از جمع قیمت اجزا ('.$sum->format().') باشد.');
        }

        $priceBefore = $bundle?->price_toman;

        $bundle = $this->db->transaction(function () use ($bundle, $title, $description, $slug, $price, $keys): Bundle {
            $bundle ??= new Bundle(['uuid' => (string) Str::uuid7(), 'status' => BundleStatus::Draft]);
            $bundle->fill(['title' => $title, 'description' => $description, 'slug' => $slug, 'price_toman' => $price->toman])->save();

            $bundle->items()->delete();

            foreach ($keys as $sort => $key) {
                [$kind, $ref] = explode(':', $key, 2);
                BundleItem::query()->create(['bundle_id' => $bundle->id, 'kind' => $kind, 'ref' => $ref, 'sort' => $sort]);
            }

            return $bundle->refresh();
        });

        $this->events->dispatch(new BundleSaved($bundle, $priceBefore, $actorId));

        return $bundle;
    }
}
