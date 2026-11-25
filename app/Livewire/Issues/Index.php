<?php

namespace App\Livewire\Issues;

use App\Models\MaterialIssue;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Material issues')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $issues = MaterialIssue::query()
            ->with(['project', 'warehouse', 'issuedTo'])
            ->withCount('items')
            ->orderByDesc('issued_date')
            ->orderByDesc('code')
            ->paginate(25);

        return view('livewire.issues.index', [
            'issues' => $issues,
        ]);
    }
}
