<?php

namespace App\Livewire\Equipment;

use App\Enums\AssetStatus;
use App\Models\Equipment;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Equipment')]
class Index extends Component
{
    use WithPagination;

    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $equipment = Equipment::query()
            ->with('currentProject')
            ->withCount('usages')
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->orderBy('code')
            ->paginate(25);

        return view('livewire.equipment.index', [
            'equipment' => $equipment,
            'statuses' => AssetStatus::cases(),
        ]);
    }
}
