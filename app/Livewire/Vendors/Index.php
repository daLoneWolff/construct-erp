<?php

namespace App\Livewire\Vendors;

use App\Models\Vendor;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Vendors')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $vendors = Vendor::query()
            ->withCount('purchaseOrders')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('pan', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.vendors.index', [
            'vendors' => $vendors,
        ]);
    }
}
