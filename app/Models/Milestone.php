<?php

namespace App\Models;

use App\Enums\MilestoneStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'project_id',
        'title',
        'description',
        'target_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'status' => MilestoneStatus::class,
        ];
    }

    public function isOverdue(): bool
    {
        return $this->status !== MilestoneStatus::Completed
            && $this->target_date->isBefore(today());
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<ProjectJob, $this> Unified tasks (R18). */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectJob::class);
    }

    /** @return HasMany<ProjectJob, $this> */
    public function projectJobs(): HasMany
    {
        return $this->hasMany(ProjectJob::class);
    }
}
