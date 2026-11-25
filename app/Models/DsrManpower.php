<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DsrManpower extends Model
{
    use BelongsToCompany;

    protected $table = 'dsr_manpower';

    /**
     * Trades offered on the DSR manpower rows.
     *
     * @var list<string>
     */
    public const TRADES = [
        'Mason', 'Helper', 'Carpenter', 'Steel fixer', 'Plumber', 'Electrician',
        'Painter', 'Driver', 'Operator', 'Foreman', 'Surveyor', 'Labor',
    ];

    protected $fillable = ['company_id',
        'daily_site_report_id', 'trade', 'count', 'hours'];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'hours' => 'float',
        ];
    }

    public function dailySiteReport(): BelongsTo
    {
        return $this->belongsTo(DailySiteReport::class);
    }
}
