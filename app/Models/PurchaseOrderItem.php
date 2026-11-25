<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'description',
        'unit',
        'quantity',
        'rate_paisa',
        'amount_paisa',
        'boq_item_id',
        'received_qty',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'rate_paisa' => 'integer',
            'amount_paisa' => 'integer',
            'received_qty' => 'float',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class);
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(MaterialReceiptItem::class);
    }
}
