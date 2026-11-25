<?php

namespace App\Models;

use App\Enums\StockTransactionType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use BelongsToCompany;

    protected $table = 'stock_ledger';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'item_description',
        'unit',
        'in_qty',
        'out_qty',
        'balance_qty',
        'transaction_type',
        'reference_type',
        'reference_id',
        'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'in_qty' => 'float',
            'out_qty' => 'float',
            'balance_qty' => 'float',
            'transaction_type' => StockTransactionType::class,
            'reference_id' => 'integer',
            'transaction_date' => 'date',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
