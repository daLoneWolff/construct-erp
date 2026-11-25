<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoqClause extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id',
        'boq_header_id', 'code', 'title', 'sort_order'];

    public function header(): BelongsTo
    {
        return $this->belongsTo(BoqHeader::class, 'boq_header_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BoqItem::class)->orderBy('code');
    }
}
