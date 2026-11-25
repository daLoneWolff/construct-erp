<?php

namespace App\Livewire\Dsr;

use App\Enums\DsrStatus;
use App\Models\BoqItem;
use App\Models\DsrManpower;
use App\Models\Project;
use App\Support\DecimalFormat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New site report')]
class Create extends Component
{
    /**
     * @var list<string>
     */
    public const WEATHER = ['Clear', 'Cloudy', 'Rain', 'Windy', 'Fog'];

    public Project $project;

    public string $reportDate = '';

    public string $weather = 'Clear';

    public string $remarks = '';

    /** @var array<int, array{trade: string, count: int|string, hours: int|string}> */
    public array $manpower = [];

    /** @var array<int, array{equipment_id: int|string, hours: int|string}> */
    public array $equipment = [];

    /** @var array<int, array{boq_item_id: int|string, quantity: int|string}> */
    public array $materials = [];

    /** @var array<int, array{name: string, size: int, mime: string}> */
    public array $photos = [];

    public function mount(Project $project): void
    {
        $this->authorize('write');

        $this->project = $project;
        $this->reportDate = today()->toDateString();
        $this->manpower = [$this->blankManpower()];
        $this->equipment = [];
        $this->materials = [$this->blankMaterial()];
    }

    public function addManpower(): void
    {
        $this->manpower[] = $this->blankManpower();
    }

    public function removeManpower(int $index): void
    {
        unset($this->manpower[$index]);
        $this->manpower = array_values($this->manpower);
    }

    public function addEquipment(): void
    {
        $this->equipment[] = ['equipment_id' => '', 'hours' => 8];
    }

    public function removeEquipment(int $index): void
    {
        unset($this->equipment[$index]);
        $this->equipment = array_values($this->equipment);
    }

    public function addMaterial(): void
    {
        $this->materials[] = $this->blankMaterial();
    }

    public function removeMaterial(int $index): void
    {
        unset($this->materials[$index]);
        $this->materials = array_values($this->materials);
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function save(): void
    {
        $this->authorize('write');

        $this->manpower = array_values(array_filter(
            $this->manpower,
            fn ($row) => (int) ($row['count'] ?? 0) > 0
        ));

        $this->equipment = array_values(array_filter(
            $this->equipment,
            fn ($row) => filled($row['equipment_id'] ?? null)
        ));

        $this->materials = array_values(array_filter(
            $this->materials,
            fn ($row) => filled($row['boq_item_id'] ?? null) && (float) ($row['quantity'] ?? 0) > 0
        ));

        $this->photos = array_slice($this->photos, 0, 8);

        $this->validate([
            'reportDate' => [
                'required',
                'date',
                'before_or_equal:today',
                Rule::unique('daily_site_reports', 'report_date')
                    ->where('project_id', $this->project->id),
            ],
            'weather' => ['required', Rule::in(self::WEATHER)],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'manpower' => ['required', 'array', 'min:1'],
            'manpower.*.trade' => ['required', Rule::in(DsrManpower::TRADES)],
            'manpower.*.count' => ['required', 'integer', 'min:1', 'max:1000'],
            'manpower.*.hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'equipment' => ['array'],
            'equipment.*.equipment_id' => [
                'required',
                Rule::exists('equipment', 'id'),
            ],
            'equipment.*.hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'materials' => ['array'],
            'materials.*.boq_item_id' => [
                'required',
                Rule::exists('boq_items', 'id'),
            ],
            'materials.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'photos' => ['array'],
            'photos.*.name' => ['required', 'string', 'max:255'],
            'photos.*.size' => ['required', 'integer', 'min:0'],
            'photos.*.mime' => ['required', 'string', 'max:100'],
        ], [
            'reportDate.unique' => __('A site report already exists for this project on this date.'),
        ]);

        $boqItems = BoqItem::query()
            ->forProject($this->project)
            ->whereIn('id', collect($this->materials)->pluck('boq_item_id'))
            ->get()
            ->keyBy('id');

        foreach ($this->materials as $index => $row) {
            if (! $boqItems->has((int) $row['boq_item_id'])) {
                $this->addError("materials.{$index}.boq_item_id", __('This BOQ item belongs to another project.'));

                return;
            }
        }

        $report = DB::transaction(function () use ($boqItems) {
            $report = $this->project->dailySiteReports()->create([
                'report_date' => $this->reportDate,
                'submitted_by_employee_id' => auth()->user()->employee_id
                    ?? $this->project->employees()->value('employees.id'),
                'weather' => $this->weather,
                'manpower_total' => collect($this->manpower)->sum(fn ($row) => (int) $row['count']),
                'remarks' => $this->remarks ?: null,
                'status' => DsrStatus::Submitted,
            ]);

            foreach ($this->manpower as $row) {
                $report->manpower()->create([
                    'trade' => $row['trade'],
                    'count' => (int) $row['count'],
                    'hours' => (float) $row['hours'],
                ]);
            }

            foreach ($this->equipment as $row) {
                $report->equipment()->create([
                    'equipment_id' => (int) $row['equipment_id'],
                    'hours' => (float) $row['hours'],
                ]);
            }

            foreach ($this->materials as $row) {
                $item = $boqItems->get((int) $row['boq_item_id']);

                $report->materials()->create([
                    'boq_item_id' => $item->id,
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'quantity' => (float) $row['quantity'],
                ]);
            }

            foreach ($this->photos as $photo) {
                $record = $report->photos()->create([
                    'filename' => $photo['name'],
                    'size_bytes' => (int) $photo['size'],
                    'mime' => $photo['mime'],
                    'path_mock' => '',
                ]);

                $record->update([
                    'path_mock' => '/mock/'.sprintf('0000000%d-0000-4000-8000-%012d', $record->id % 10, $record->id),
                ]);
            }

            return $report;
        });

        session()->flash('success', __('Site report saved and submitted for approval.'));

        $this->redirect(route('dsr.show', $report), navigate: true);
    }

    private function blankManpower(): array
    {
        return ['trade' => DsrManpower::TRADES[0], 'count' => 0, 'hours' => 8];
    }

    private function blankMaterial(): array
    {
        return ['boq_item_id' => '', 'quantity' => ''];
    }

    public function render()
    {
        return view('livewire.dsr.create', [
            'trades' => DsrManpower::TRADES,
            'weatherOptions' => self::WEATHER,
            'displayPhotos' => array_map(
                fn (array $photo) => $photo + [
                    'size_label' => DecimalFormat::format($photo['size'] / 1024, 0).' KB',
                ],
                $this->photos,
            ),
            'boqItems' => BoqItem::query()->forProject($this->project)->orderBy('code')->get(['id', 'code', 'description', 'unit']),
            'equipmentOptions' => $this->project->equipment()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }
}
