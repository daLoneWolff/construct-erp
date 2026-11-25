<?php

namespace App\Livewire\Requisitions;

use App\Enums\RequisitionStatus;
use App\Models\MaterialRequisition;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Material requisitions')]
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
        $requisitions = MaterialRequisition::query()
            ->with(['project', 'raisedBy'])
            ->withCount('items')
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('livewire.requisitions.index', [
            'requisitions' => $requisitions,
            'statuses' => RequisitionStatus::cases(),
            'pendingCount' => MaterialRequisition::query()->where('status', RequisitionStatus::Pending)->count(),
        ]);
    }
}
