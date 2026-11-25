<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialReceipt extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'purchase_order_id',
        'warehouse_id',
        'received_date',
        'received_by_employee_id',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'status' => ReceiptStatus::class,
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by_employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialReceiptItem::class);
    }
}
