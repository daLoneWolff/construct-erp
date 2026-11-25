<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Projects')]
class Index extends Component
{
    use WithPagination;

    public function render(CalculationService $calc)
    {
        $projects = Project::query()->orderBy('code')->paginate(25);

        return view('livewire.projects.index', [
            'projects' => $projects,
            'progress' => $calc->boqProjectProgressForProjects($projects->pluck('id')->all()),
        ]);
    }
}
