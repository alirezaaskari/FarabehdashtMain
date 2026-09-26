<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Domain;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

/**
 * سهم یک جزء از یک خرید بسته، همان لحظه خرید.
 *
 * allocated = سهم این جزء از قیمت بسته، به نسبت قیمت تکی؛ از آن owner_amount
 * به صاحب جزء و platform_amount به پلتفرم می‌رسد.
 *
 * @property int $id
 * @property int $bundle_purchase_id
 * @property string $kind
 * @property string $ref
 * @property string $title
 * @property int $list_price_toman
 * @property int $allocated_toman
 * @property int|null $owner_user_id
 * @property int $owner_amount_toman
 * @property int $platform_amount_toman
 */
final class BundlePurchaseLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'bundle_purchase_id',
        'kind',
        'ref',
        'title',
        'list_price_toman',
        'allocated_toman',
        'owner_user_id',
        'owner_amount_toman',
        'platform_amount_toman',
    ];

    public function ownerAmount(): Money
    {
        return Money::toman($this->owner_amount_toman);
    }

    public function platformAmount(): Money
    {
        return Money::toman($this->platform_amount_toman);
    }
}
