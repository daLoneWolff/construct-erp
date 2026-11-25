<?php

namespace App\Livewire\Projects;

use App\Livewire\Projects\Concerns\ProjectForm;
use App\Models\Project;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit project')]
class Edit extends Component
{
    use ProjectForm;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->authorize('manage');
        $this->fillFromProject($project);
    }

    public function save()
    {
        $this->authorize('manage');
        $this->validate();

        $this->project->update($this->payload());

        session()->flash('success', 'Project updated.');

        return $this->redirectRoute('projects.show', $this->project);
    }

    public function render()
    {
        return view('livewire.projects.edit', [
            'managers' => $this->managers(),
        ]);
    }
}
