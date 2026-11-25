<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon|null $bluebook_expiry
 * @property Carbon|null $insurance_expiry
 * @property Carbon|null $pollution_expiry
 * @property Carbon|null $route_permit_expiry
 */
class Vehicle extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'registration_no',
        'type',
        'make_model',
        'bluebook_expiry',
        'insurance_expiry',
        'pollution_expiry',
        'route_permit_expiry',
        'status',
        'current_project_id',
    ];

    protected function casts(): array
    {
        return [
            'bluebook_expiry' => 'date',
            'insurance_expiry' => 'date',
            'pollution_expiry' => 'date',
            'route_permit_expiry' => 'date',
            'status' => AssetStatus::class,
        ];
    }

    public function currentProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'current_project_id');
    }
}
