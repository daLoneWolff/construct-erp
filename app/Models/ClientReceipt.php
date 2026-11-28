<?php

namespace App\Models;

use App\Enums\ClientReceiptType;
use App\Enums\PaymentMethod;
use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $receipt_no
 * @property int $amount_paisa
 * @property Carbon $received_on
 * @property PaymentMethod $method
 * @property ClientReceiptType $type
 */
class ClientReceipt extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'project_id',
        'running_bill_id',
        'receipt_no',
        'received_on',
        'amount_paisa',
        'type',
        'method',
        'reference_no',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_paisa' => 'integer',
            'received_on' => 'date',
            'method' => PaymentMethod::class,
            'type' => ClientReceiptType::class,
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<RunningBill, $this> */
    public function runningBill(): BelongsTo
    {
        return $this->belongsTo(RunningBill::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
