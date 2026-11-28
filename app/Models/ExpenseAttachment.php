<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\DecimalFormat;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAttachment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'expense_id',
        'filename',
        'size_bytes',
        'mime',
        'path_mock',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function sizeLabel(): Attribute
    {
        return Attribute::get(fn (): string => DecimalFormat::format($this->size_bytes / 1024, 1).' KB');
    }

    /** @return BelongsTo<Expense, $this> */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
