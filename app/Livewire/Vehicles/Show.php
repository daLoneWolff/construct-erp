<?php

namespace App\Livewire\Vehicles;

use App\Enums\AssetStatus;
use App\Models\Project;
use App\Models\Vehicle;
use App\Services\CalculationService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Vehicle')]
class Show extends Component
{
    public Vehicle $vehicle;

    public string $assignProjectId = '';

    public function mount(Vehicle $vehicle): void
    {
        $this->vehicle = $vehicle;
        $this->assignProjectId = $vehicle->current_project_id !== null
            ? (string) $vehicle->current_project_id
            : '';
    }

    public function assignProject(): void
    {
        $this->authorize('manage');

        $this->validate([
            'assignProjectId' => ['nullable', Rule::exists('projects', 'id')],
        ]);

        $this->vehicle->update([
            'current_project_id' => $this->assignProjectId !== '' ? (int) $this->assignProjectId : null,
            'status' => $this->assignProjectId !== ''
                ? AssetStatus::OnSite
                : AssetStatus::Available,
        ]);

        session()->flash('success', __('Vehicle assignment updated.'));
    }

    public function render(CalculationService $calc)
    {
        $status = $calc->vehicleStatus($this->vehicle);

        return view('livewire.vehicles.show', [
            'projects' => Project::query()->orderBy('code')->get(['id', 'code', 'name']),
            'expiry' => $status,
            'documents' => collect($status['documents'])->map(fn (array $row, string $key) => [
                'label' => match ($key) {
                    'bluebook' => __('Bluebook'),
                    'insurance' => __('Insurance'),
                    'pollution' => __('Pollution'),
                    default => __('Route permit'),
                },
                'days' => $row['days'],
                'badge' => $row['badge'],
            ]),
        ]);
    }
}
