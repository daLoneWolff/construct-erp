<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialReceiptItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'material_receipt_id',
        'purchase_order_item_id',
        'ordered_qty',
        'received_qty',
        'rejected_qty',
    ];

    protected function casts(): array
    {
        return [
            'ordered_qty' => 'float',
            'received_qty' => 'float',
            'rejected_qty' => 'float',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(MaterialReceipt::class, 'material_receipt_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * Gross delivered quantity minus defective quantity actually stocked.
     */
    public function acceptedQty(): Attribute
    {
        return Attribute::get(fn (): float => (float) $this->received_qty - (float) $this->rejected_qty);
    }
}
