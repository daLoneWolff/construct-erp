<?php

namespace App\Livewire\Projects;

use App\Livewire\Projects\Concerns\ProjectForm;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $this->validatePartners();

        if ($this->getErrorBag()->isNotEmpty()) {
            throw ValidationException::withMessages($this->getErrorBag()->messages());
        }

        $project = DB::transaction(function () {
            $project = Project::create($this->payload());

            foreach ($this->normalizedPartners() as $partner) {
                $project->partners()->create($partner);
            }

            return $project;
        });

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
