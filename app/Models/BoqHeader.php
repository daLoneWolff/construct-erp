<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoqHeader extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id',
        'project_id', 'code', 'title', 'sort_order'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function clauses()
    {
        return $this->hasMany(BoqClause::class)->orderBy('sort_order');
    }
}
