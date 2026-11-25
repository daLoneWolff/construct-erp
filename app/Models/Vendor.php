<?php

namespace App\Models;

use App\Enums\VendorStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property float $tds_rate
 */
class Vendor extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'pan',
        'vat',
        'address',
        'contact_person',
        'phone',
        'email',
        'tds_rate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tds_rate' => 'float',
            'status' => VendorStatus::class,
        ];
    }

    /** @return HasMany<PurchaseOrder, $this> */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
