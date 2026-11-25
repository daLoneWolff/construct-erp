<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * R18 compatibility shim: legacy Task model now maps to unified project_jobs tasks.
 * Prefer ProjectJob for new code.
 */
class Task extends ProjectJob
{
    protected $table = 'project_jobs';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'due_date' => 'date',
        ]);
    }

    public function getDueDateAttribute()
    {
        return $this->due_on;
    }

    public function setDueDateAttribute($value): void
    {
        $this->attributes['due_on'] = $value;
    }

    public function projectJob(): BelongsTo
    {
        return $this->belongsTo(ProjectJob::class, 'id', 'id');
    }
}
