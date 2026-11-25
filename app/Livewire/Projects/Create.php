<?php

namespace App\Livewire\Projects;

use App\Livewire\Projects\Concerns\ProjectForm;
use App\Models\Project;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New project')]
class Create extends Component
{
    use ProjectForm;

    public function save()
    {
        $this->authorize('manage');
        $this->validate();

        $project = Project::create($this->payload());

        session()->flash('success', 'Project created.');

        return $this->redirectRoute('projects.show', $project);
    }

    public function render()
    {
        return view('livewire.projects.create', [
            'managers' => $this->managers(),
        ]);
    }
}
