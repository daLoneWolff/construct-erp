<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DsrEquipment extends Model
{
    use BelongsToCompany;

    protected $table = 'dsr_equipment';

    protected $fillable = ['company_id',
        'daily_site_report_id', 'equipment_id', 'hours'];

    protected function casts(): array
    {
        return [
            'hours' => 'float',
        ];
    }

    public function dailySiteReport(): BelongsTo
    {
        return $this->belongsTo(DailySiteReport::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
