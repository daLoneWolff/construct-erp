<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialIssue extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'project_id',
        'warehouse_id',
        'issued_to_employee_id',
        'issued_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function issuedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'issued_to_employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialIssueItem::class);
    }
}
