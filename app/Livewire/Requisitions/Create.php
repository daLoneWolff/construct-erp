<?php

namespace App\Livewire\Requisitions;

use App\Enums\RequisitionStatus;
use App\Models\BoqItem;
use App\Models\MaterialRequisition;
use App\Models\Project;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New requisition')]
class Create extends Component
{
    public string $projectId = '';

    public string $requiredBy = '';

    public string $notes = '';

    /** @var array<int, array{description: string, unit: string, quantity: int|string, boq_item_id: int|string, notes: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->authorize('write');

        $this->requiredBy = today()->addDays(7)->toDateString();
        $this->items = [$this->blankItem()];
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedProjectId(): void
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['boq_item_id'] = '';
        }
    }

    public function fillFromBoq(int $index): void
    {
        $item = BoqItem::query()
            ->forProject($this->projectId)
            ->find($this->items[$index]['boq_item_id'] ?? null);

        if ($item === null) {
            return;
        }

        $this->items[$index]['description'] = $item->description;
        $this->items[$index]['unit'] = $item->unit;
    }

    public function save(): void
    {
        $this->authorize('write');

        $this->items = array_values(array_filter(
            $this->items,
            fn ($row) => filled($row['description'] ?? null) && (float) ($row['quantity'] ?? 0) > 0
        ));

        $this->validate([
            'projectId' => ['required', Rule::exists('projects', 'id')],
            'requiredBy' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.boq_item_id' => ['nullable', Rule::exists('boq_items', 'id')],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $project = Project::query()->findOrFail($this->projectId);

        if (BoqItem::query()->forProject($project)
            ->whereIn('id', collect($this->items)->pluck('boq_item_id')->filter())
            ->count() !== collect($this->items)->pluck('boq_item_id')->filter()->count()) {
            $this->addError('items', __('Every BOQ item must belong to the selected project.'));

            return;
        }

        $raisedBy = auth()->user()->employee_id
            ?? $project->employees()->value('employees.id');

        if ($raisedBy === null) {
            $this->addError('projectId', __('The selected project has no employee to raise this requisition.'));

            return;
        }

        $requisition = DB::transaction(function () use ($project, $raisedBy) {
            $requisition = MaterialRequisition::create([
                'code' => DocumentNumber::requisition(),
                'project_id' => $project->id,
                'raised_by_employee_id' => $raisedBy,
                'required_by' => $this->requiredBy,
                'notes' => $this->notes ?: null,
                'status' => RequisitionStatus::Pending,
            ]);

            foreach ($this->items as $row) {
                $requisition->items()->create([
                    'description' => $row['description'],
                    'unit' => $row['unit'],
                    'quantity' => (float) $row['quantity'],
                    'boq_item_id' => filled($row['boq_item_id'] ?? null) ? (int) $row['boq_item_id'] : null,
                    'notes' => $row['notes'] ?: null,
                ]);
            }

            return $requisition;
        });

        session()->flash('success', __('Requisition :code submitted for approval.', ['code' => $requisition->code]));

        $this->redirect(route('requisitions.show', $requisition), navigate: true);
    }

    public function render()
    {
        return view('livewire.requisitions.create', [
            'projects' => Project::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']),
            'boqItems' => BoqItem::query()->forProject($this->projectId)->orderBy('code')->get(['id', 'code', 'description', 'unit']),
        ]);
    }

    /** @return array{description: string, unit: string, quantity: int|string, boq_item_id: int|string, notes: string} */
    private function blankItem(): array
    {
        return ['description' => '', 'unit' => 'bag', 'quantity' => '', 'boq_item_id' => '', 'notes' => ''];
    }
}
