<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $payment_no
 * @property int $amount_paisa
 * @property Carbon $paid_on
 * @property PaymentMethod $method
 */
class ContractorPayment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'payment_no',
        'project_id',
        'project_job_id',
        'contractor_id',
        'amount_paisa',
        'paid_on',
        'method',
        'reference_no',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_paisa' => 'integer',
            'paid_on' => 'date',
            'method' => PaymentMethod::class,
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<ProjectJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(ProjectJob::class, 'project_job_id');
    }

    /** @return BelongsTo<Contractor, $this> */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
