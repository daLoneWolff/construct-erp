<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentUsage extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'equipment_id',
        'project_id',
        'usage_date',
        'hours',
        'operator_name',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
            'hours' => 'float',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
