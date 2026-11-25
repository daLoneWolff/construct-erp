<?php

namespace App\Livewire\Company;

use App\Models\User;
use App\Rules\Pan;
use App\Services\CompanySetupService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Create your company')]
class Create extends Component
{
    public string $name = '';

    public string $pan = '';

    public string $vat = '';

    public string $address = '';

    public string $district = '';

    public string $fiscal_year = '';

    public function mount(): void
    {
        if (auth()->user()?->current_company_id !== null) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function createCompany(CompanySetupService $setup): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $input = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'pan' => ['required', 'string', new Pan],
            'vat' => ['nullable', 'string', new Pan],
            'address' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:100'],
            'fiscal_year' => ['required', 'string', 'max:20'],
        ]);

        $setup->createForUser($user, $input);

        session()->flash('success', __('Your company workspace is ready.'));

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('company.create');
    }
}
