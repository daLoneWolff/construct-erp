<?php

namespace App\Livewire\Vehicles;

use App\Enums\AssetStatus;
use App\Models\Vehicle;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Vehicles')]
class Index extends Component
{
    public string $status = '';

    public function render(CalculationService $calc)
    {
        $vehicles = Vehicle::query()
            ->with('currentProject')
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->orderBy('registration_no')
            ->get()
            ->map(function (Vehicle $vehicle) use ($calc) {
                $status = $calc->vehicleStatus($vehicle);

                return [
                    'vehicle' => $vehicle,
                    'expiry' => $status,
                    'expiryLabel' => match ($status['badge']) {
                        'red' => __('Expired'),
                        'yellow' => __('Expiring soon'),
                        'green' => __('Valid'),
                        default => __('No documents'),
                    },
                ];
            });

        $counts = [
            'red' => $vehicles->where('expiry.badge', 'red')->count(),
            'yellow' => $vehicles->where('expiry.badge', 'yellow')->count(),
            'green' => $vehicles->where('expiry.badge', 'green')->count(),
        ];

        return view('livewire.vehicles.index', [
            'vehicles' => $vehicles,
            'statuses' => AssetStatus::cases(),
            'counts' => $counts,
        ]);
    }
}
