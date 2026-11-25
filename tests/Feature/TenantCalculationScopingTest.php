<?php

use App\Enums\EmploymentType;
use App\Enums\ProjectStatus;
use App\Models\BoqClause;
use App\Models\BoqHeader;
use App\Models\BoqItem;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\MaterialRequisition;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\User;
use App\Services\CalculationService;
use App\Services\TenantService;
use App\Support\DocumentNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function t4Company(string $name, string $fiscalYear = '2083/84'): Company
{
    return Company::create([
        'name' => $name,
        'pan' => '601234567',
        'vat' => '601234567',
        'address' => 'Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => $fiscalYear,
    ]);
}

function t4Project(Company $company, User $manager, string $code, string $name): Project
{
    return Project::create([
        'company_id' => $company->id,
        'code' => $code,
        'name' => $name,
        'client_name' => 'Test Client',
        'contract_value_paisa' => 100000000,
        'location_ward' => '1',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $manager->id,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);
}

function t4Contractor(string $pan, string $name): Contractor
{
    return Contractor::create([
        'company_id' => app(TenantService::class)->currentCompanyId(),
        'name' => $name,
        'phone' => '9841000000',
        'address' => 'Lalitpur',
        'pan' => $pan,
        'is_active' => true,
    ]);
}

function t4Job(Project $project, Contractor $contractor, int $agreedPaisa): ProjectJob
{
    return ProjectJob::create([
        'company_id' => $project->company_id,
        'project_id' => $project->id,
        'contractor_id' => $contractor->id,
        'title' => 'Masonry works',
        'agreed_amount_paisa' => $agreedPaisa,
        'status' => 'todo',
        'starts_on' => today()->subWeek()->toDateString(),
        'due_on' => today()->addWeek()->toDateString(),
    ]);
}

function t4ContractorPayment(Project $project, ProjectJob $job, Contractor $contractor, string $no, int $amount): ContractorPayment
{
    return ContractorPayment::create([
        'company_id' => $project->company_id,
        'payment_no' => $no,
        'project_id' => $project->id,
        'project_job_id' => $job->id,
        'contractor_id' => $contractor->id,
        'amount_paisa' => $amount,
        'paid_on' => today()->toDateString(),
        'method' => 'bank_transfer',
    ]);
}

test('contractor payment numbering restarts for each company', function () {
    $alpha = tenantCompany(['name' => 'Alpha Builders', 'fiscal_year' => '2083/84']);
    $alphaOwner = tenantUser('t4-alpha-owner@tenant.test', [], $alpha);
    $alphaProject = t4Project($alpha, $alphaOwner, 'PRJ-T4-A', 'Alpha Project');
    $alphaContractor = t4Contractor('100000001', 'Alpha Contractor');
    $alphaJob = t4Job($alphaProject, $alphaContractor, 10000000);

    $calc = app(CalculationService::class);
    $prefix = 'CTP-'.today()->year.'-';

    expect($calc->nextContractorPaymentNo())->toBe($prefix.'0001');

    t4ContractorPayment($alphaProject, $alphaJob, $alphaContractor, $prefix.'0001', 1000000);

    expect($calc->nextContractorPaymentNo())->toBe($prefix.'0002');

    $beta = t4Company('Beta Builders', '2084/85');
    app(TenantService::class)->setCurrentCompany($beta);
    $betaOwner = tenantUser('t4-beta-owner@tenant.test', [], $beta);
    $betaProject = t4Project($beta, $betaOwner, 'PRJ-T4-B', 'Beta Project');
    $betaContractor = t4Contractor('200000002', 'Beta Contractor');
    $betaJob = t4Job($betaProject, $betaContractor, 10000000);

    expect($calc->nextContractorPaymentNo())->toBe($prefix.'0001');

    t4ContractorPayment($betaProject, $betaJob, $betaContractor, $prefix.'0001', 2000000);

    expect($calc->nextContractorPaymentNo())->toBe($prefix.'0002');

    app(TenantService::class)->setCurrentCompany($alpha);

    expect($calc->nextContractorPaymentNo())->toBe($prefix.'0002');
});

test('document numbers and fiscal years are scoped to the current company', function () {
    $alpha = tenantCompany(['name' => 'Alpha Builders', 'fiscal_year' => '2083/84']);
    tenantUser('t4-doc-alpha@tenant.test', [], $alpha);

    expect(DocumentNumber::equipment())->toBe('EQ-001')
        ->and(DocumentNumber::purchaseOrder())->toBe('PO-2083-001');

    Equipment::create([
        'company_id' => $alpha->id,
        'code' => 'EQ-001',
        'name' => 'Alpha Excavator',
        'type' => 'Excavator',
        'ownership' => 'owned',
        'daily_rate_paisa' => 1000000,
    ]);

    expect(DocumentNumber::equipment())->toBe('EQ-002');

    $beta = t4Company('Beta Builders', '2084/85');
    app(TenantService::class)->setCurrentCompany($beta);
    tenantUser('t4-doc-beta@tenant.test', [], $beta);

    expect(DocumentNumber::equipment())->toBe('EQ-001')
        ->and(DocumentNumber::purchaseOrder())->toBe('PO-2084-001');
});

test('dashboard and payment export include only the current company', function () {
    $alpha = tenantCompany(['name' => 'Alpha Builders', 'fiscal_year' => '2083/84']);
    $alphaOwner = tenantUser('t4-export-alpha@tenant.test', [], $alpha);
    $alphaProject = t4Project($alpha, $alphaOwner, 'PRJ-T4-A', 'Alpha Export Project');
    $alphaContractor = t4Contractor('100000003', 'Alpha Export Contractor');
    $alphaJob = t4Job($alphaProject, $alphaContractor, 10000000);
    t4ContractorPayment($alphaProject, $alphaJob, $alphaContractor, 'CTP-'.today()->year.'-0001', 1250000);

    $beta = t4Company('Beta Builders', '2084/85');
    app(TenantService::class)->setCurrentCompany($beta);
    $betaOwner = tenantUser('t4-export-beta@tenant.test', [], $beta);
    $betaProject = t4Project($beta, $betaOwner, 'PRJ-T4-B', 'Beta Export Project');
    $betaContractor = t4Contractor('200000004', 'Beta Export Contractor');
    $betaJob = t4Job($betaProject, $betaContractor, 20000000);
    t4ContractorPayment($betaProject, $betaJob, $betaContractor, 'CTP-'.today()->year.'-0001', 2500000);

    app(TenantService::class)->setCurrentCompany($alpha);

    $this->actingAs($alphaOwner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Alpha Export Project')
        ->assertDontSee('Beta Export Project');

    $content = $this->actingAs($alphaOwner)
        ->get(route('payments.export'))
        ->assertOk()
        ->streamedContent();

    expect($content)->toContain('Alpha Export Project')
        ->toContain('Alpha Export Contractor')
        ->not->toContain('Beta Export Project')
        ->not->toContain('Beta Export Contractor');
});

test('raw reconciliation queries ignore another company requisition rows', function () {
    $alpha = tenantCompany(['name' => 'Alpha Builders', 'fiscal_year' => '2083/84']);
    $alphaOwner = tenantUser('t4-recon-alpha@tenant.test', [], $alpha);
    $project = t4Project($alpha, $alphaOwner, 'PRJ-T4-RECON', 'Alpha Recon Project');

    $employee = Employee::create([
        'employee_code' => 'T4-EMP-01',
        'name' => 'T4 Employee',
        'designation' => 'Mason',
        'trade' => 'Civil',
        'department' => 'Construction',
        'employment_type' => EmploymentType::Monthly,
        'monthly_basic_paisa' => 3000000,
        'pan' => '601111199',
        'ssf_enrolled' => false,
        'joined_at' => today()->subMonths(6)->toDateString(),
        'status' => 'active',
    ]);

    $header = BoqHeader::create([
        'project_id' => $project->id,
        'code' => 'H-T4',
        'title' => 'T4 header',
        'sort_order' => 1,
    ]);
    $clause = BoqClause::create([
        'boq_header_id' => $header->id,
        'code' => 'C-T4',
        'title' => 'T4 clause',
        'sort_order' => 1,
    ]);
    $item = BoqItem::create([
        'boq_clause_id' => $clause->id,
        'code' => 'BI-T4',
        'description' => 'T4 cement',
        'unit' => 'bag',
        'quantity' => 1000,
        'rate_paisa' => 100000,
        'amount_paisa' => 100000000,
        'executed_quantity' => 0,
    ]);

    $alphaMr = MaterialRequisition::create([
        'code' => 'MR-T4-A',
        'project_id' => $project->id,
        'raised_by_employee_id' => $employee->id,
        'required_by' => today()->addWeek()->toDateString(),
        'status' => 'pending',
    ]);
    $alphaMr->items()->create([
        'description' => 'T4 cement',
        'unit' => 'bag',
        'quantity' => 10,
        'boq_item_id' => $item->id,
    ]);

    $beta = t4Company('Beta Builders', '2084/85');
    $betaMrId = DB::table('material_requisitions')->insertGetId([
        'company_id' => $beta->id,
        'code' => 'MR-T4-B',
        'project_id' => $project->id,
        'raised_by_employee_id' => $employee->id,
        'required_by' => today()->addWeek()->toDateString(),
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('material_requisition_items')->insert([
        'company_id' => $beta->id,
        'material_requisition_id' => $betaMrId,
        'description' => 'Cross-company cement',
        'unit' => 'bag',
        'quantity' => 999,
        'boq_item_id' => $item->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(TenantService::class)->setCurrentCompany($alpha);

    $row = app(CalculationService::class)->reconciliation($project)
        ->firstWhere('boq_item_id', $item->id);

    expect($row)->not->toBeNull()
        ->and($row['requisitioned_qty'])->toBe(10.0);
});
