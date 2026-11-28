<?php

namespace App\Livewire\Contractors;

use App\Enums\CompanyRole;
use App\Enums\Role;
use App\Models\CompanyUser;
use App\Models\Contractor;
use App\Models\User;
use App\Rules\Pan;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contractor')]
class Form extends Component
{
    public ?Contractor $contractor = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $pan = '';

    public bool $is_active = true;

    public string $notes = '';

    public bool $enable_portal_access = false;

    public string $portal_password = '';

    public string $portal_password_confirmation = '';

    public function mount(?Contractor $contractor = null): void
    {
        $this->authorize('manage');

        $this->contractor = $contractor?->loadMissing('user');

        if ($contractor) {
            $this->name = $contractor->name;
            $this->phone = $contractor->phone ?? '';
            $this->email = $contractor->email ?? '';
            $this->address = $contractor->address ?? '';
            $this->pan = $contractor->pan ?? '';
            $this->is_active = (bool) $contractor->is_active;
            $this->notes = $contractor->notes ?? '';
            $this->enable_portal_access = $contractor->user_id !== null;
        }
    }

    public function save(TenantService $tenant): void
    {
        $this->authorize('manage');

        $company = $tenant->currentCompany();
        abort_unless($company !== null, 403);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [$this->enable_portal_access ? 'required' : 'nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'pan' => [
                'nullable',
                'string',
                new Pan,
                Rule::unique('contractors', 'pan')->ignore($this->contractor?->id),
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'enable_portal_access' => ['boolean'],
        ];

        $linkedUser = $this->contractor?->user;

        if ($this->enable_portal_access) {
            $passwordRules = $linkedUser
                ? ['nullable', 'string', Password::default(), 'confirmed']
                : ['required', 'string', Password::default(), 'confirmed'];

            $rules['portal_password'] = $passwordRules;
        }

        $this->validate($rules);

        if ($this->enable_portal_access) {
            $this->validatePortalEmail($company->id, $linkedUser?->id);
            if ($this->getErrorBag()->isNotEmpty()) {
                throw ValidationException::withMessages($this->getErrorBag()->toArray());
            }
        }

        $attributes = [
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'pan' => $this->pan ?: null,
            'is_active' => $this->is_active,
            'notes' => $this->notes ?: null,
        ];

        $contractor = DB::transaction(function () use ($attributes, $company, $linkedUser): Contractor {
            if ($this->contractor) {
                $this->contractor->update($attributes);
                $contractor = $this->contractor->fresh();
            } else {
                $contractor = Contractor::create($attributes);
            }

            $this->syncPortalAccess($contractor, $company->id, $linkedUser);

            return $contractor->fresh(['user']);
        });

        session()->flash('success', $this->contractor ? __('Contractor updated.') : __('Contractor created.'));

        $this->redirect(route('contractors.show', $contractor), navigate: true);
    }

    private function validatePortalEmail(int $companyId, ?int $linkedUserId): void
    {
        $email = mb_strtolower(trim($this->email));

        $existingUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $existingUser) {
            return;
        }

        if ($linkedUserId !== null && $existingUser->id === $linkedUserId) {
            return;
        }

        $linkedToAnotherContractor = Contractor::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('user_id', $existingUser->id)
            ->when($this->contractor, fn ($q) => $q->where('id', '!=', $this->contractor->id))
            ->exists();

        if ($linkedToAnotherContractor) {
            $this->addError('email', __('This email is already used by another contractor portal account in this company.'));

            return;
        }

        if ($existingUser->role !== Role::Contractor) {
            $this->addError('email', __('This email belongs to an existing non-contractor user. Use a different email for contractor portal access.'));
        }
    }

    private function syncPortalAccess(Contractor $contractor, int $companyId, ?User $linkedUser): void
    {
        if (! $this->enable_portal_access) {
            if ($linkedUser) {
                $this->revokePortalAccess($contractor, $companyId, $linkedUser);
            }

            return;
        }

        $email = mb_strtolower(trim($this->email));
        $user = $linkedUser ?? User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user && $user->role !== Role::Contractor && $user->id !== $linkedUser?->id) {
            // Should have been caught by validation, but guard anyway.
            return;
        }

        if (! $user) {
            $user = User::create([
                'name' => $this->name,
                'email' => $email,
                'password' => Hash::make($this->portal_password),
                'role' => Role::Contractor,
                'email_verified_at' => now(),
                'current_company_id' => $companyId,
            ]);
        } else {
            $updates = [
                'name' => $this->name,
                'email' => $email,
                'role' => Role::Contractor,
            ];

            if ($user->email_verified_at === null) {
                $updates['email_verified_at'] = now();
            }

            if ($user->current_company_id === null) {
                $updates['current_company_id'] = $companyId;
            }

            if ($this->portal_password !== '') {
                $updates['password'] = Hash::make($this->portal_password);
            }

            $user->forceFill($updates)->save();
        }

        CompanyUser::query()->updateOrCreate(
            ['company_id' => $companyId, 'user_id' => $user->id],
            ['role' => CompanyRole::Contractor, 'joined_at' => now()]
        );

        if ($user->current_company_id === null) {
            $user->forceFill(['current_company_id' => $companyId])->save();
        }

        $contractor->forceFill(['user_id' => $user->id])->save();
    }

    private function revokePortalAccess(Contractor $contractor, int $companyId, User $user): void
    {
        $contractor->forceFill(['user_id' => null])->save();

        CompanyUser::query()
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->where('role', CompanyRole::Contractor)
            ->delete();

        $hasOtherMemberships = CompanyUser::query()->where('user_id', $user->id)->exists();
        $ownsCompany = $user->ownedCompany()->exists();

        if (! $hasOtherMemberships && ! $ownsCompany && $user->role === Role::Contractor) {
            if ($user->current_company_id === $companyId) {
                $user->forceFill(['current_company_id' => null])->save();
            }
        }
    }

    public function render()
    {
        $linkedUser = $this->contractor?->user;

        return view('livewire.contractors.form', [
            'isEdit' => $this->contractor !== null,
            'linkedUser' => $linkedUser,
        ]);
    }
}
