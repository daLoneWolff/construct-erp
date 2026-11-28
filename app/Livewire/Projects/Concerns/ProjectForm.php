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

    /** @var array<int, array{name: string, share_percent: string}> */
    public array $partners = [];

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
            'partners' => ['array'],
            'partners.*.name' => ['required', 'string', 'max:255'],
            'partners.*.share_percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
        ];
    }

    protected function fillFromProject(Project $project): void
    {
        $project->loadMissing('partners');

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
        $this->partners = $project->partners
            ->map(fn ($partner): array => [
                'name' => $partner->name,
                'share_percent' => (string) $partner->share_percent,
            ])
            ->all();
    }

    public function addPartner(): void
    {
        $this->partners[] = ['name' => '', 'share_percent' => ''];
    }

    public function removePartner(int $index): void
    {
        unset($this->partners[$index]);
        $this->partners = array_values($this->partners);
    }

    protected function validatePartners(): void
    {
        if ($this->partners === []) {
            return;
        }

        $names = [];
        $total = 0.0;

        foreach ($this->partners as $index => $partner) {
            $name = trim((string) ($partner['name'] ?? ''));
            $key = mb_strtolower($name);

            if ($name !== '' && isset($names[$key])) {
                $this->addError("partners.{$index}.name", __('Partner name must be unique within the project.'));
            }
            $names[$key] = true;

            $total += (float) ($partner['share_percent'] ?? 0);
        }

        if (round($total, 2) !== 100.0) {
            $this->addError('partners', __('JV partner shares must total 100%.'));
        }
    }

    /** @return array<int, array{name: string, share_percent: string}> */
    protected function normalizedPartners(): array
    {
        return array_values(array_map(fn (array $partner): array => [
            'name' => trim((string) ($partner['name'] ?? '')),
            'share_percent' => number_format((float) ($partner['share_percent'] ?? 0), 2, '.', ''),
        ], $this->partners));
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
