<?php

namespace App\Livewire\Contractors;

use App\Models\Contractor;
use App\Rules\Pan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contractor')]
class Form extends Component
{
    public ?Contractor $contractor = null;

    public string $name = '';

    public string $phone = '';

    public string $address = '';

    public string $pan = '';

    public bool $is_active = true;

    public string $notes = '';

    public function mount(?Contractor $contractor = null): void
    {
        $this->authorize('manage');

        $this->contractor = $contractor;

        if ($contractor) {
            $this->name = $contractor->name;
            $this->phone = $contractor->phone ?? '';
            $this->address = $contractor->address ?? '';
            $this->pan = $contractor->pan ?? '';
            $this->is_active = (bool) $contractor->is_active;
            $this->notes = $contractor->notes ?? '';
        }
    }

    public function save(): void
    {
        $this->authorize('manage');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'pan' => [
                'nullable',
                'string',
                new Pan,
                Rule::unique('contractors', 'pan')->ignore($this->contractor?->id),
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $attributes = [
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'pan' => $this->pan ?: null,
            'is_active' => $this->is_active,
            'notes' => $this->notes ?: null,
        ];

        if ($this->contractor) {
            $this->contractor->update($attributes);
            $contractor = $this->contractor;
            session()->flash('success', __('Contractor updated.'));
        } else {
            $contractor = Contractor::create($attributes);
            session()->flash('success', __('Contractor created.'));
        }

        $this->redirect(route('contractors.show', $contractor), navigate: true);
    }

    public function render()
    {
        return view('livewire.contractors.form', [
            'isEdit' => $this->contractor !== null,
        ]);
    }
}
