<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DsrMaterial extends Model
{
    use BelongsToCompany;

    protected $table = 'dsr_materials';

    protected $fillable = [
        'company_id',
        'daily_site_report_id',
        'description',
        'unit',
        'quantity',
        'boq_item_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
        ];
    }

    public function dailySiteReport(): BelongsTo
    {
        return $this->belongsTo(DailySiteReport::class);
    }

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class);
    }
}
