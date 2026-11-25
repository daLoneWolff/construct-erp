<?php

namespace App\Livewire\Boq;

use App\Models\BoqClause;
use App\Models\BoqItem;
use App\Models\Project;
use App\Support\Npr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Bill of quantities')]
class Index extends Component
{
    public Project $project;

    public bool $showForm = false;

    public bool $showImport = false;

    public ?int $editingId = null;

    public ?int $clauseId = null;

    public string $code = '';

    public string $description = '';

    public string $unit = 'cum';

    public string $quantity = '0';

    public string $rate = '0.00';

    public string $executed = '0';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'clauseId' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:255'],
            'unit' => ['required', Rule::in(BoqItem::UNITS)],
            'quantity' => ['required', 'numeric', 'min:0'],
            'rate' => ['required', 'numeric', 'min:0'],
            'executed' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function create(?int $clauseId = null): void
    {
        $this->authorize('manage');

        $this->resetValidation();
        $this->editingId = null;
        $this->clauseId = $clauseId ?? $this->firstClauseId();
        $this->code = '';
        $this->description = '';
        $this->unit = 'cum';
        $this->quantity = '0';
        $this->rate = '0.00';
        $this->executed = '0';
        $this->showForm = true;
    }

    public function edit(int $itemId): void
    {
        $this->authorize('manage');

        $item = $this->itemQuery()->findOrFail($itemId);

        $this->resetValidation();
        $this->editingId = $item->id;
        $this->clauseId = $item->boq_clause_id;
        $this->code = $item->code;
        $this->description = $item->description;
        $this->unit = $item->unit;
        $this->quantity = (string) $item->quantity;
        $this->rate = Npr::toInput($item->rate_paisa);
        $this->executed = (string) $item->executed_quantity;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manage');

        $this->validate();

        $clause = BoqClause::query()
            ->whereHas('header', fn ($query) => $query->where('project_id', $this->project->id))
            ->findOrFail($this->clauseId);

        $duplicate = $this->itemQuery()
            ->where('code', $this->code)
            ->when($this->editingId, fn ($query) => $query->whereKeyNot($this->editingId))
            ->exists();

        if ($duplicate) {
            $this->addError('code', __('This item code is already used in this BOQ.'));

            return;
        }

        $quantity = (float) $this->quantity;
        $ratePaisa = Npr::toPaisa($this->rate);

        $attributes = [
            'boq_clause_id' => $clause->id,
            'code' => $this->code,
            'description' => $this->description,
            'unit' => $this->unit,
            'quantity' => $quantity,
            'rate_paisa' => $ratePaisa,
            'amount_paisa' => (int) round($quantity * $ratePaisa),
            'executed_quantity' => (float) $this->executed,
        ];

        if ($this->editingId) {
            $this->itemQuery()->findOrFail($this->editingId)->update($attributes);
            session()->flash('success', __('BOQ item updated.'));
        } else {
            BoqItem::create($attributes);
            session()->flash('success', __('BOQ item added.'));
        }

        $this->showForm = false;
    }

    public function delete(int $itemId): void
    {
        $this->authorize('manage');

        $item = $this->itemQuery()->withCount([
            'requisitionItems',
            'purchaseOrderItems',
            'issueItems',
            'dsrMaterials',
        ])->findOrFail($itemId);

        $links = $item->requisition_items_count
            + $item->purchase_order_items_count
            + $item->issue_items_count
            + $item->dsr_materials_count;

        if ($links > 0) {
            session()->flash('error', __('This item is already referenced by procurement, issues or site reports and cannot be deleted.'));

            return;
        }

        $item->delete();

        session()->flash('success', __('BOQ item deleted.'));
    }

    private function itemQuery()
    {
        return BoqItem::query()->forProject($this->project);
    }

    private function firstClauseId(): ?int
    {
        return BoqClause::query()
            ->whereHas('header', fn ($query) => $query->where('project_id', $this->project->id))
            ->value('id');
    }

    public function render()
    {
        $headers = $this->project->boqHeaders()
            ->with(['clauses.items' => fn ($query) => $query->orderBy('code')])
            ->orderBy('sort_order')
            ->get();

        $clauses = BoqClause::query()
            ->whereHas('header', fn ($query) => $query->where('project_id', $this->project->id))
            ->join('boq_headers', 'boq_headers.id', '=', 'boq_clauses.boq_header_id')
            ->orderBy('boq_headers.sort_order')
            ->orderBy('boq_clauses.sort_order')
            ->select('boq_clauses.*')
            ->get();

        $progress = $headers->flatMap->clauses->flatMap->items->mapWithKeys(
            fn (BoqItem $item) => [$item->id => app(\App\Services\CalculationService::class)->boqItemProgress($item)]
        );

        return view('livewire.boq.index', [
            'headers' => $headers,
            'clauses' => $clauses,
            'progress' => $progress,
            'units' => BoqItem::UNITS,
        ]);
    }
}
