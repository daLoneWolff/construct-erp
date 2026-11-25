<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractor extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'address',
        'pan',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<ProjectJob, $this> */
    public function jobs(): HasMany
    {
        return $this->hasMany(ProjectJob::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ContractorPayment::class);
    }
}
