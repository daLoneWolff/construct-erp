<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Services\CalculationService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectJob extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'project_id',
        'milestone_id',
        'contractor_id',
        'assignee_employee_id',
        'title',
        'description',
        'agreed_amount_paisa',
        'status',
        'starts_on',
        'due_on',
    ];

    protected function casts(): array
    {
        return [
            'agreed_amount_paisa' => 'integer',
            'status' => JobStatus::class,
            'starts_on' => 'date',
            'due_on' => 'date',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_employee_id');
    }

    public function isPayable(): bool
    {
        return $this->contractor_id !== null;
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    /** @return BelongsTo<Contractor, $this> */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    /** @deprecated R18: legacy subtasks were migrated into unified project_jobs rows. */
    public function tasks(): HasMany
    {
        return $this->hasMany(self::class, 'id', 'id')->whereRaw('1 = 0');
    }

    /** @return HasMany<ContractorPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(ContractorPayment::class);
    }

    public function paidAmountPaisa(): Attribute
    {
        return Attribute::get(fn (): int => app(CalculationService::class)->jobPaidPaisa($this));
    }

    public function balancePaisa(): Attribute
    {
        return Attribute::get(fn (): int => app(CalculationService::class)->jobBalancePaisa($this));
    }
}
