<?php

namespace App\Livewire\Projects\Concerns;

use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\Rule;

trait ProjectForm
{
    public string $code = '';

    public string $name = '';

    public string $client_name = '';

    public string $contract_value_npr = '';

    public string $location_ward = '';

    public string $location_municipality = '';

    public string $location_district = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $project_manager_id = '';

    public string $advance_npr = '0';

    public string $status = 'active';

    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('projects', 'code')->ignore($this->ignoredProjectId())],
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'contract_value_npr' => ['required', 'numeric', 'min:0'],
            'location_ward' => ['required', 'string', 'max:255'],
            'location_municipality' => ['required', 'string', 'max:255'],
            'location_district' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'project_manager_id' => ['required', 'exists:users,id'],
            'advance_npr' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
        ];
    }

    protected function fillFromProject(Project $project): void
    {
        $this->code = $project->code;
        $this->name = $project->name;
        $this->client_name = $project->client_name;
        $this->contract_value_npr = (string) ($project->contract_value_paisa / 100);
        $this->location_ward = $project->location_ward;
        $this->location_municipality = $project->location_municipality;
        $this->location_district = $project->location_district;
        $this->start_date = $project->start_date->toDateString();
        $this->end_date = $project->end_date->toDateString();
        $this->project_manager_id = (string) $project->project_manager_id;
        $this->advance_npr = (string) ($project->advance_paisa / 100);
        $this->status = $project->status->value;
    }

    protected function payload(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'client_name' => $this->client_name,
            'contract_value_paisa' => (int) round((float) $this->contract_value_npr * 100),
            'location_ward' => $this->location_ward,
            'location_municipality' => $this->location_municipality,
            'location_district' => $this->location_district,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'project_manager_id' => (int) $this->project_manager_id,
            'advance_paisa' => (int) round((float) $this->advance_npr * 100),
            'status' => $this->status,
        ];
    }

    protected function managers()
    {
        return User::query()
            ->where('role', Role::Admin->value)
            ->orderBy('name')
            ->get();
    }

    protected function ignoredProjectId(): ?int
    {
        return isset($this->project) ? $this->project->id : null;
    }
}
