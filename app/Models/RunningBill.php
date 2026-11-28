<?php

namespace App\Models;

use App\Enums\RunningBillStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunningBill extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'project_id',
        'bill_number',
        'period_from',
        'period_to',
        'work_certified_paisa',
        'advance_adjusted_paisa',
        'net_payable_paisa',
        'certified_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bill_number' => 'integer',
            'period_from' => 'date',
            'period_to' => 'date',
            'work_certified_paisa' => 'integer',
            'advance_adjusted_paisa' => 'integer',
            'net_payable_paisa' => 'integer',
            'certified_date' => 'date',
            'status' => RunningBillStatus::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function clientReceipts(): HasMany
    {
        return $this->hasMany(ClientReceipt::class);
    }
}
