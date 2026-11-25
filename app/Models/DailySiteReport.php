<?php

namespace App\Models;

use App\Enums\DsrStatus;
use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon|null $report_date
 * @property int|null $manpower_total
 */
class DailySiteReport extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'project_id',
        'report_date',
        'submitted_by_employee_id',
        'weather',
        'manpower_total',
        'remarks',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'manpower_total' => 'integer',
            'status' => DsrStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'submitted_by_employee_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function manpower(): HasMany
    {
        return $this->hasMany(DsrManpower::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(DsrEquipment::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(DsrMaterial::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DsrPhoto::class);
    }
}
