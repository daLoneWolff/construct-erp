<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property int|null $owner_user_id
 * @property string $pan
 * @property string|null $vat
 * @property string $address
 * @property string $district
 * @property string $fiscal_year
 * @property string|null $logo_mock
 * @property Carbon|null $onboarding_completed_at
 * @property bool $is_demo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Company extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'owner_user_id',
        'pan',
        'vat',
        'address',
        'district',
        'fiscal_year',
        'logo_mock',
        'onboarding_completed_at',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'onboarding_completed_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** @return BelongsToMany<User, $this, CompanyUser> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps()
            ->using(CompanyUser::class);
    }

    /** @return HasMany<CompanyUser, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }
}
