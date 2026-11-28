<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|null $company_id
 * @property ProjectStatus $status
 */
class Project extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'client_name',
        'contract_value_paisa',
        'location_ward',
        'location_municipality',
        'location_district',
        'start_date',
        'end_date',
        'project_manager_id',
        'advance_paisa',
        'advance_adjusted_paisa',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'contract_value_paisa' => 'integer',
            'advance_paisa' => 'integer',
            'advance_adjusted_paisa' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProjectStatus::class,
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    /** @return HasMany<ProjectPartner, $this> */
    public function partners(): HasMany
    {
        return $this->hasMany(ProjectPartner::class)->orderBy('id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_employee')
            ->withPivot(['role_on_project', 'assigned_at'])
            ->withTimestamps();
    }

    public function boqHeaders(): HasMany
    {
        return $this->hasMany(BoqHeader::class);
    }

    /** @return HasMany<Milestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    /** @return HasMany<ProjectJob, $this> Unified tasks (R18). */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectJob::class);
    }

    /** @return HasMany<ProjectJob, $this> */
    public function jobs(): HasMany
    {
        return $this->hasMany(ProjectJob::class);
    }

    /** @return HasMany<ContractorPayment, $this> */
    public function contractorPayments(): HasMany
    {
        return $this->hasMany(ContractorPayment::class);
    }

    public function clientReceipts(): HasMany
    {
        return $this->hasMany(ClientReceipt::class);
    }

    public function dailySiteReports(): HasMany
    {
        return $this->hasMany(DailySiteReport::class);
    }

    /** @return HasMany<MaterialRequisition, $this> */
    public function requisitions(): HasMany
    {
        return $this->hasMany(MaterialRequisition::class);
    }

    /** @return HasMany<PurchaseOrder, $this> */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function materialIssues(): HasMany
    {
        return $this->hasMany(MaterialIssue::class);
    }

    public function runningBills(): HasMany
    {
        return $this->hasMany(RunningBill::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'current_project_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'current_project_id');
    }
}
