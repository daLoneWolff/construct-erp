<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\DecimalFormat;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DsrPhoto extends Model
{
    use BelongsToCompany;

    protected $table = 'dsr_photos';

    protected $fillable = [
        'company_id',
        'daily_site_report_id',
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
        return Attribute::get(fn (): string => DecimalFormat::format($this->size_bytes / 1024, 0).' KB');
    }

    public function dailySiteReport(): BelongsTo
    {
        return $this->belongsTo(DailySiteReport::class);
    }
}
