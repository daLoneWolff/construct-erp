<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoqItem extends Model
{
    use BelongsToCompany;

    /**
     * Units accepted by the BOQ screens and the CSV importer.
     *
     * @var list<string>
     */
    public const UNITS = [
        'cum', 'sqm', 'kg', 'bag', 'nos', 'ton', 'ltr', 'm', 'rmt',
        'each', 'day', 'month', 'ls',
    ];

    protected $fillable = [
        'company_id',
        'boq_clause_id',
        'code',
        'description',
        'unit',
        'quantity',
        'rate_paisa',
        'amount_paisa',
        'executed_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'executed_quantity' => 'float',
            'rate_paisa' => 'integer',
            'amount_paisa' => 'integer',
        ];
    }

    public function clause(): BelongsTo
    {
        return $this->belongsTo(BoqClause::class, 'boq_clause_id');
    }

    /**
     * Limit the query to items belonging to one project's BOQ.
     */
    public function scopeForProject(Builder $query, Project|int|string $project): Builder
    {
        $projectId = $project instanceof Project ? $project->id : (int) $project;

        return $query->whereHas(
            'clause.header',
            fn (Builder $header) => $header->where('project_id', $projectId)
        );
    }

    public function requisitionItems(): HasMany
    {
        return $this->hasMany(MaterialRequisitionItem::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function issueItems(): HasMany
    {
        return $this->hasMany(MaterialIssueItem::class);
    }

    public function dsrMaterials(): HasMany
    {
        return $this->hasMany(DsrMaterial::class);
    }
}
