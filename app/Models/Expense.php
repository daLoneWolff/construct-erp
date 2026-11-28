<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $expense_no
 * @property int $amount_paisa
 * @property Carbon $expense_date
 * @property ExpenseCategory $category
 * @property PaymentMethod $method
 */
class Expense extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'expense_no',
        'expense_date',
        'amount_paisa',
        'category',
        'method',
        'payee',
        'reference_no',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_paisa' => 'integer',
            'expense_date' => 'date',
            'category' => ExpenseCategory::class,
            'method' => PaymentMethod::class,
        ];
    }

    /** @return HasMany<ExpenseAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
