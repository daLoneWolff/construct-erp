<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\OwnershipType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use BelongsToCompany;

    protected $table = 'equipment';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'ownership',
        'hourly_rate_paisa',
        'daily_rate_paisa',
        'status',
        'current_project_id',
    ];

    protected function casts(): array
    {
        return [
            'ownership' => OwnershipType::class,
            'status' => AssetStatus::class,
            'hourly_rate_paisa' => 'integer',
            'daily_rate_paisa' => 'integer',
        ];
    }

    public function currentProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'current_project_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(EquipmentUsage::class);
    }

    public function dsrEntries(): HasMany
    {
        return $this->hasMany(DsrEquipment::class);
    }
}
