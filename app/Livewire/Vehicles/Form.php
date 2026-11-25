<?php

namespace App\Livewire\Vehicles;

use App\Enums\AssetStatus;
use App\Models\Project;
use App\Models\Vehicle;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Vehicle')]
class Form extends Component
{
    public ?Vehicle $vehicle = null;

    public string $registration_no = '';

    public string $type = '';

    public string $make_model = '';

    public string $bluebook_expiry = '';

    public string $insurance_expiry = '';

    public string $pollution_expiry = '';

    public string $route_permit_expiry = '';

    public string $status = 'available';

    public string $currentProjectId = '';

    public function mount(?Vehicle $vehicle = null): void
    {
        $this->authorize('manage');

        $this->vehicle = $vehicle;
        $this->registration_no = $vehicle?->registration_no ?? '';
        $this->type = $vehicle?->type ?? '';
        $this->make_model = $vehicle?->make_model ?? '';
        $this->bluebook_expiry = $vehicle?->bluebook_expiry?->toDateString() ?? today()->addYear()->toDateString();
        $this->insurance_expiry = $vehicle?->insurance_expiry?->toDateString() ?? today()->addYear()->toDateString();
        $this->pollution_expiry = $vehicle?->pollution_expiry?->toDateString() ?? today()->addYear()->toDateString();
        $this->route_permit_expiry = $vehicle?->route_permit_expiry?->toDateString() ?? '';
        $this->status = $vehicle?->status->value ?? AssetStatus::Available->value;
        $this->currentProjectId = $vehicle?->current_project_id !== null
            ? (string) $vehicle->current_project_id
            : '';
    }

    public function save(): void
    {
        $this->authorize('manage');

        $this->validate([
            'registration_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'registration_no')->ignore($this->vehicle?->id),
            ],
            'type' => ['required', 'string', 'max:100'],
            'make_model' => ['required', 'string', 'max:255'],
            'bluebook_expiry' => ['required', 'date'],
            'insurance_expiry' => ['required', 'date'],
            'pollution_expiry' => ['required', 'date'],
            'route_permit_expiry' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(AssetStatus::class)],
            'currentProjectId' => ['nullable', Rule::exists('projects', 'id')],
        ]);

        $attributes = [
            'registration_no' => $this->registration_no,
            'type' => $this->type,
            'make_model' => $this->make_model,
            'bluebook_expiry' => $this->bluebook_expiry,
            'insurance_expiry' => $this->insurance_expiry,
            'pollution_expiry' => $this->pollution_expiry,
            'route_permit_expiry' => $this->route_permit_expiry !== '' ? $this->route_permit_expiry : null,
            'status' => $this->status,
            'current_project_id' => $this->currentProjectId !== '' ? (int) $this->currentProjectId : null,
        ];

        if ($this->vehicle) {
            $this->vehicle->update($attributes);
            $vehicle = $this->vehicle;
            session()->flash('success', __('Vehicle updated.'));
        } else {
            $vehicle = Vehicle::create($attributes);
            session()->flash('success', __('Vehicle created.'));
        }

        $this->redirect(route('vehicles.show', $vehicle), navigate: true);
    }

    public function render()
    {
        return view('livewire.vehicles.form', [
            'projects' => Project::query()->orderBy('code')->get(['id', 'code', 'name']),
            'statuses' => AssetStatus::cases(),
            'isEdit' => $this->vehicle !== null,
        ]);
    }
}
