<?php

namespace App\Livewire\Equipment;

use App\Enums\AssetStatus;
use App\Models\Equipment;
use App\Models\Project;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Equipment')]
class Show extends Component
{
    public Equipment $equipment;

    public string $assignProjectId = '';

    public function mount(Equipment $equipment): void
    {
        $this->equipment = $equipment;
        $this->assignProjectId = $equipment->current_project_id !== null
            ? (string) $equipment->current_project_id
            : '';
    }

    public function assignProject(): void
    {
        $this->authorize('manage');

        $this->validate([
            'assignProjectId' => ['nullable', Rule::exists('projects', 'id')],
        ]);

        $this->equipment->update([
            'current_project_id' => $this->assignProjectId !== '' ? (int) $this->assignProjectId : null,
            'status' => $this->assignProjectId !== ''
                ? AssetStatus::OnSite
                : AssetStatus::Available,
        ]);

        session()->flash('success', __('Equipment assignment updated.'));
    }

    public function render()
    {
        return view('livewire.equipment.show', [
            'projects' => Project::query()->orderBy('code')->get(['id', 'code', 'name']),
            'usages' => $this->equipment->usages()
                ->with('project')
                ->orderByDesc('usage_date')
                ->get(),
        ]);
    }
}
