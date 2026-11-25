<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Models\Concerns\BelongsToCompany;
use App\Support\DecimalFormat;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'project_id',
        'category',
        'title',
        'filename',
        'size_bytes',
        'mime',
        'path_mock',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'size_bytes' => 'integer',
        ];
    }

    public function sizeLabel(): Attribute
    {
        return Attribute::get(fn (): string => DecimalFormat::format($this->size_bytes / 1024, 1).' KB');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
