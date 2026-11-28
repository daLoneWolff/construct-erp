<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property Carbon $joined_at
 */
class Employee extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_code',
        'name',
        'designation',
        'trade',
        'department',
        'employment_type',
        'daily_wage_paisa',
        'monthly_basic_paisa',
        'pan',
        'ssf_enrolled',
        'ssf_no',
        'joined_at',
        'phone',
        'email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'status' => EmployeeStatus::class,
            'daily_wage_paisa' => 'integer',
            'monthly_basic_paisa' => 'integer',
            'ssf_enrolled' => 'boolean',
            'joined_at' => 'date',
        ];
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_employee')
            ->withPivot(['role_on_project', 'assigned_at'])
            ->withTimestamps();
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectJob::class, 'assignee_employee_id');
    }
}
