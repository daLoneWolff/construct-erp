<?php

namespace App\Livewire\Equipment;

use App\Enums\AssetStatus;
use App\Enums\OwnershipType;
use App\Models\Equipment;
use App\Models\Project;
use App\Support\DocumentNumber;
use App\Support\Npr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Equipment')]
class Form extends Component
{
    public ?Equipment $equipment = null;

    public string $code = '';

    public string $name = '';

    public string $type = '';

    public string $ownership = 'owned';

    public string $hourly_rate = '';

    public string $daily_rate = '';

    public string $status = 'available';

    public string $currentProjectId = '';

    public function mount(?Equipment $equipment = null): void
    {
        $this->authorize('manage');

        $this->equipment = $equipment;
        $this->code = $equipment?->code ?? DocumentNumber::equipment();
        $this->name = $equipment?->name ?? '';
        $this->type = $equipment?->type ?? '';
        $this->ownership = $equipment?->ownership->value ?? OwnershipType::Owned->value;
        $this->hourly_rate = $equipment?->hourly_rate_paisa !== null
            ? Npr::toInput($equipment->hourly_rate_paisa)
            : '';
        $this->daily_rate = $equipment?->daily_rate_paisa !== null
            ? Npr::toInput($equipment->daily_rate_paisa)
            : '';
        $this->status = $equipment?->status->value ?? AssetStatus::Available->value;
        $this->currentProjectId = $equipment?->current_project_id !== null
            ? (string) $equipment->current_project_id
            : '';
    }

    public function save(): void
    {
        $this->authorize('manage');

        $this->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('equipment', 'code')->ignore($this->equipment?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'ownership' => ['required', Rule::enum(OwnershipType::class)],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(AssetStatus::class)],
            'currentProjectId' => ['nullable', Rule::exists('projects', 'id')],
        ]);

        $attributes = [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'ownership' => $this->ownership,
            'hourly_rate_paisa' => $this->hourly_rate !== '' ? Npr::toPaisa($this->hourly_rate) : null,
            'daily_rate_paisa' => $this->daily_rate !== '' ? Npr::toPaisa($this->daily_rate) : null,
            'status' => $this->status,
            'current_project_id' => $this->currentProjectId !== '' ? (int) $this->currentProjectId : null,
        ];

        if ($this->equipment) {
            $this->equipment->update($attributes);
            $equipment = $this->equipment;
            session()->flash('success', __('Equipment updated.'));
        } else {
            $equipment = Equipment::create($attributes);
            session()->flash('success', __('Equipment created.'));
        }

        $this->redirect(route('equipment.show', $equipment), navigate: true);
    }

    public function render()
    {
        return view('livewire.equipment.form', [
            'projects' => Project::query()->orderBy('code')->get(['id', 'code', 'name']),
            'ownershipTypes' => OwnershipType::cases(),
            'statuses' => AssetStatus::cases(),
            'isEdit' => $this->equipment !== null,
        ]);
    }
}
