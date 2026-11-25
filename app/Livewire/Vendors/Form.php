<?php

namespace App\Livewire\Vendors;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Rules\Pan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Vendor')]
class Form extends Component
{
    public ?Vendor $vendor = null;

    public string $name = '';

    public string $pan = '';

    public string $vat = '';

    public string $address = '';

    public string $contact_person = '';

    public string $phone = '';

    public string $email = '';

    public string $tds_rate = '1.50';

    public string $status = 'active';

    public function mount(?Vendor $vendor = null): void
    {
        $this->authorize('manage');

        $this->vendor = $vendor;

        if ($vendor) {
            $this->name = $vendor->name;
            $this->pan = $vendor->pan;
            $this->vat = $vendor->vat ?? '';
            $this->address = $vendor->address;
            $this->contact_person = $vendor->contact_person;
            $this->phone = $vendor->phone;
            $this->email = $vendor->email ?? '';
            $this->tds_rate = number_format((float) $vendor->tds_rate, 2, '.', '');
            $this->status = $vendor->status->value;
        }
    }

    public function save(): void
    {
        $this->authorize('manage');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'pan' => ['required', 'string', new Pan, Rule::unique('vendors', 'pan')->ignore($this->vendor?->id)],
            'vat' => ['nullable', 'string', new Pan],
            'address' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'tds_rate' => ['required', 'numeric', Rule::in(['1.50', '15.00'])],
            'status' => ['required', Rule::enum(VendorStatus::class)],
        ]);

        $attributes = [
            'name' => $this->name,
            'pan' => $this->pan,
            'vat' => $this->vat ?: null,
            'address' => $this->address,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email ?: null,
            'tds_rate' => (float) $this->tds_rate,
            'status' => $this->status,
        ];

        if ($this->vendor) {
            $this->vendor->update($attributes);
            $vendor = $this->vendor;
            session()->flash('success', __('Vendor updated.'));
        } else {
            $vendor = Vendor::create($attributes);
            session()->flash('success', __('Vendor created.'));
        }

        $this->redirect(route('vendors.show', $vendor), navigate: true);
    }

    public function render()
    {
        return view('livewire.vendors.form', [
            'isEdit' => $this->vendor !== null,
            'tdsRates' => ['1.50', '15.00'],
            'statuses' => VendorStatus::cases(),
        ]);
    }
}
